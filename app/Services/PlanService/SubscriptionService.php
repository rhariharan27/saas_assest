<?php

namespace App\Services\PlanService;

use App\Enums\OrderStatus;
use App\Enums\PlanDurationType;
use App\Enums\SubscriptionStatus;
use App\Models\Settings;
use App\Models\SuperAdmin\Order;
use App\Models\SuperAdmin\Plan;
use App\Models\SuperAdmin\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Log;

class SubscriptionService implements ISubscriptionService
{

  public function getPlanTotalAmount(Plan $plan, int $usersCount): float
  {
    return $plan->base_price + ($plan->per_user_price * $usersCount);
  }

  public function activatePlan(Order $order): void
  {
    if ($order->status != OrderStatus::COMPLETED) {
      return;
    }

    $plan = Plan::find($order->plan_id);
    $user = auth()->user();

    $user->plan_id = $plan->id;
    $user->plan_expired_date = $this->generatePlanExpiryDate($plan);
    $user->save();

    $subscription = new Subscription();
    $subscription->user_id = $user->id;
    $subscription->plan_id = $plan->id;
    $subscription->users_count = $plan->included_users + $order->additional_users;
    $subscription->additional_users = $order->additional_users;
    $subscription->per_user_price = $plan->per_user_price;
    $subscription->total_price = $order->total_amount;
    $subscription->start_date = now();
    $subscription->end_date = $user->plan_expired_date;
    $subscription->status = SubscriptionStatus::ACTIVE;
    $subscription->tenant_id = $user->email;

    $subscription->save();

    // Refresh Plan Access for Tenants to set correct user limits
    $this->refreshPlanAccessForTenants($plan->id);
  }

  public function generatePlanExpiryDate(Plan $plan): DateTime
  {
    return match ($plan->duration_type) {
      PlanDurationType::MONTHS => now()->addMonths($plan->duration),
      PlanDurationType::YEARS => now()->addYears($plan->duration),
      default => now()->addDays($plan->duration),
    };
  }

  public function renewPlan(Order $order): void
  {
    if ($order->status != OrderStatus::COMPLETED) {
      return;
    }

    $plan = Plan::find($order->plan_id);

    $subscription = Subscription::where('user_id', $order->user_id)
      ->where('status', SubscriptionStatus::ACTIVE)
      ->first();


    if ($subscription->end_date < now()) {
      $newExpiryDate = $this->generatePlanExpiryDate($plan);
    } else {
      $newExpiryDate = $this->generatePlanExpiryDateByDate($plan, Carbon::parse($subscription->end_date));
    }

    $user = $order->user;
    $user->plan_id = $plan->id;
    $user->plan_expired_date = $newExpiryDate;
    $user->save();

    $subscription->plan_id = $plan->id;
    $subscription->users_count = $plan->included_users + $order->additional_users;
    $subscription->additional_users = $order->additional_users;
    $subscription->per_user_price = $plan->per_user_price;
    $subscription->total_price = $order->total_amount;
    $subscription->end_date = $user->plan_expired_date;
    $subscription->status = SubscriptionStatus::ACTIVE;

    $subscription->save();

    // Refresh Plan Access for Tenants to update user limits
    $this->refreshPlanAccessForTenants($plan->id);
  }

  public function generatePlanExpiryDateByDate(Plan $plan, DateTime $startDate): DateTime
  {
    return match ($plan->duration_type) {
      PlanDurationType::MONTHS => $startDate->addMonths($plan->duration),
      PlanDurationType::YEARS => $startDate->addYears($plan->duration),
      default => $startDate->addDays($plan->duration),
    };
  }

  public function addUsersToSubscription(Order $order): void
  {
    if ($order->status != OrderStatus::COMPLETED) {
      return;
    }

    $subscription = Subscription::where('user_id', $order->user_id)
      ->where('status', SubscriptionStatus::ACTIVE)
      ->first();

    $subscription->users_count += $order->additional_users;
    $subscription->additional_users += $order->additional_users;
    $subscription->total_price += $order->total_amount;
    $subscription->save();

    //Refresh Plan Access for Tenants
    $this->refreshPlanAccessForTenants($subscription->plan_id);
  }

  public function getAddUserTotalAmount(int $usersCount): float
  {
    $subscription = Subscription::where('user_id', auth()->id())
      ->where('status', SubscriptionStatus::ACTIVE)
      ->first();

    $plan = $subscription->plan;

    $perUserPricePerDay = $this->getPerUserPricePerDayByPlanType($plan);

    $differenceDays = now()->diffInDays($subscription->end_date);

    return round(($perUserPricePerDay * $differenceDays) * $usersCount, 2);

  }

  private function getPerUserPricePerDayByPlanType(Plan $plan): float
  {
    return match ($plan->duration_type) {
      PlanDurationType::MONTHS => $plan->per_user_price / 30,
      PlanDurationType::YEARS => $plan->per_user_price / 365,
      default => $plan->per_user_price,
    };
  }

  public function upgradePlan(Order $order): void
  {
    if ($order->status != OrderStatus::COMPLETED) {
      return;
    }

    $plan = Plan::find($order->plan_id);
    $user = $order->user;

    $user->plan_id = $plan->id;
    $user->plan_expired_date = $this->generatePlanExpiryDate($plan);
    $user->save();

    $subscription = Subscription::where('user_id', $user->id)
      ->where('status', SubscriptionStatus::ACTIVE)
      ->first();

    $subscription->plan_id = $plan->id;
    $subscription->users_count = $plan->included_users + $order->additional_users;
    $subscription->additional_users = $order->additional_users;
    $subscription->per_user_price = $plan->per_user_price;
    $subscription->total_price = $order->total_amount;
    $subscription->end_date = $user->plan_expired_date;

    $subscription->save();

    // Refresh Plan Access for Tenants to update user limits
    $this->refreshPlanAccessForTenants($plan->id);
  }

  public function getRenewalAmount(): float
  {
    $plan = $this->getCurrentPlan();

    return $plan->base_price + ($plan->per_user_price * $this->getSubscription()->users_count);
  }

  public function getCurrentPlan(): Plan
  {
    $user = auth()->user();

    return Plan::find($user->plan_id);
  }

  public function getSubscription(): Subscription
  {
    return Subscription::where('user_id', auth()->id())
      ->where('status', SubscriptionStatus::ACTIVE)
      ->with('plan')
      ->first();
  }

  public function getDifferencePriceForUpgrade(int $newPlanId): float
  {
    $newPlan = Plan::find($newPlanId);

    $subscription = $this->getSubscription();

    $pricePerDay = $this->getPerUserPricePerDayByPlanType($subscription->plan);

    $daysLeft = -(Carbon::parse($subscription->end_date)->diffInDays(now()));

    $balanceAmountInCurrentPlan = $pricePerDay * $daysLeft;

    // Use the current subscription's total users count for the new plan calculation
    $newPlanPrice = $newPlan->base_price + ($newPlan->per_user_price * $subscription->users_count);

    return round($newPlanPrice - $balanceAmountInCurrentPlan, 2);
  }

public function refreshPlanAccessForTenants($planId): void
  {
    $plan = Plan::find($planId); // This query correctly runs on the central DB

    // Retrieve all relevant users from the CENTRAL database *before* initializing tenancy
    $centralUsers = User::where('plan_id', $planId)->get();

    // Extract tenant IDs from these central users
    $tenantIds = $centralUsers->pluck('tenant_id')->unique();

    // Retrieve the tenants from the central database
    $tenants = Tenant::whereIn('id', $tenantIds)->get();

    // Retrieve all active subscriptions for the users linked to this plan from the CENTRAL database
    // This pre-fetches subscriptions to avoid hitting the tenant DB after context switch
    $centralUserIds = $centralUsers->pluck('id')->unique()->toArray();
    $activeSubscriptions = Subscription::whereIn('user_id', $centralUserIds)
        ->where('status', SubscriptionStatus::ACTIVE)
        ->get();

    Log::info('Syncing Plan Access for Tenants: ' . json_encode($tenants));

    foreach ($tenants as $tenant) {
      // Find the specific central user for this tenant from the pre-fetched collection
      $user = $centralUsers->where('tenant_id', $tenant->id)->first();
      
      if (!$user) {
        Log::info('No Central User Found for Tenant to Sync: ' . $tenant->name);
        continue;
      }
      
      // Find the subscription for this central user from the pre-fetched collection
      $subscription = $activeSubscriptions->where('user_id', $user->id)->first();

      if (!$subscription) {
        Log::info('No Active Subscription Found for Central User ' . $user->id . ' for Tenant to Sync: ' . $tenant->name);
        continue;
      }

      // NOW initialize tenancy. All subsequent model calls will hit the tenant DB.
      tenancy()->initialize($tenant);
      
      // These models (Settings) should be configured to use the tenant's database
      // The `Settings` model should reside in your tenant application's `App\Models` directory.
      $settings = Settings::first(); 
      $settings->accessible_module_routes = []; // This should be updated by processTenantSettingsForAccessRoutesByPlan
      $settings->available_modules = $plan->modules; // $plan is from central DB, its modules are applied to tenant settings
      $settings->employees_limit = $subscription->users_count; // $subscription is from central DB
      $settings->save();

      // Important: Ensure Settings model exists in the tenant's application and its table
      // in the tenant database does NOT have a 'plan_id' column or any conflicting logic.

      Log::info('Plan Access Synced for Tenant: ' . $tenant->name . ' with ' . $subscription->users_count . ' users');
    }
  }

  public function processTenantSettingsForAccessRoutesByPlan(): Settings
  {
    Log::info('Processing Tenant Settings for Access Routes by Plan');
    $tenantVerticalMenuJson = file_get_contents(base_path('resources/menu/tenantVerticalMenu.json'));
    $tenantVerticalMenuData = json_decode($tenantVerticalMenuJson);

    $settings = Settings::first();
    //Log::info('Settings: ' . json_encode($settings));

    $accessModules = $settings->available_modules;
    //Log::info('Available Modules From Settings: ' . json_encode($accessModules));

    $allModulesFromMenu = $tenantVerticalMenuData->menu;

    foreach ($allModulesFromMenu as $key => $module) {

      if (isset($module->menuHeader))
        continue;

      if (isset($module->addon) && in_array($module->addon, $accessModules)) {
        if (is_array($module->slug)) {
          foreach ($module->slug as $slug) {
            $availableRoutes[] = $slug;
          }
        } else {
          $availableRoutes[] = $module->slug;
        }
      } else if (isset($module->submenu)) {
        foreach ($module->submenu as $subMenu) {
          if (isset($subMenu->addon) && in_array($subMenu->addon, $accessModules)) {
            if (is_array($subMenu->slug)) {
              foreach ($subMenu->slug as $slug) {
                $availableRoutes[] = $slug;
              }
            } else {
              $availableRoutes[] = $subMenu->slug;
            }
          }
        }
      } else {
        $availableRoutes[] = $module->slug;
      }
    }

    $settings->accessible_module_routes = $availableRoutes;

    // Log::info('Generated Accessible Routes: ' . json_encode($availableRoutes));

    $settings->save();

    return $settings;
  }
}
