<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .invoice-header h1 {
            color: #3490dc;
            margin-bottom: 5px;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-info-item {
            margin-bottom: 20px;
        }
        .invoice-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .invoice-details th, 
        .invoice-details td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .invoice-details th {
            background-color: #f2f2f2;
        }
        .invoice-total {
            text-align: right;
            margin-top: 30px;
            font-size: 18px;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <h1>{{ config('variables.templateFullName') }}</h1>
        <p>Invoice #{{ $order->id }}</p>
    </div>
    
    <div class="invoice-info">
        <div class="invoice-info-item">
            <h3>From:</h3>
            <p>{{ config('variables.creatorName') }}</p>
            <p>Email: {{ config('variables.supportEmail') }}</p>
            <p>Phone: {{ config('variables.supportNumber') }}</p>
        </div>
        
        <div class="invoice-info-item">
            <h3>To:</h3>
            <p>{{ $order->user->first_name . ' ' . $order->user->last_name }}</p>
            <p>Email: {{ $order->user->email }}</p>
            <p>Phone: {{ $order->user->phone }}</p>
        </div>
        
        <div class="invoice-info-item">
            <h3>Invoice Details:</h3>
            <p><strong>Date:</strong> {{ $order->created_at->format('d M, Y') }}</p>
            <p><strong>Payment Method:</strong> {{ $order->payment_gateway }}</p>
            <p><strong>Order Type:</strong> {{ $order->type }}</p>
        </div>
    </div>
    
    <table class="invoice-details">
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Plan Subscription</td>
                <td>{{ $order->plan->name }} ({{ $order->plan->duration }} {{ $order->plan->duration_type->value }})</td>
                <td>1</td>
                <td>{{ $settings->currency_symbol }}{{ $order->plan->base_price }}</td>
                <td>{{ $settings->currency_symbol }}{{ $order->plan->base_price }}</td>
            </tr>
            @if($order->additional_users > 0)
            <tr>
                <td>Additional Users</td>
                <td>Extra user licenses</td>
                <td>{{ $order->additional_users }}</td>
                <td>{{ $settings->currency_symbol }}{{ $order->plan->per_user_price }}</td>
                <td>{{ $settings->currency_symbol }}{{ $order->additional_users * $order->plan->per_user_price }}</td>
            </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right;"><strong>Total Amount:</strong></td>
                <td><strong>{{ $settings->currency_symbol }}{{ $order->amount }}</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="invoice-total">
        <p>Total Paid: {{ $settings->currency_symbol }}{{ $order->amount }}</p>
    </div>
    
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>&copy; {{ date('Y') }} {{ config('variables.templateFullName') }}. All rights reserved.</p>
    </div>
</body>
</html>
