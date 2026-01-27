@php
    $title = __('Client Details')
@endphp
@section('title', __('Client Details'))

@extends('layouts/layoutMaster')

  @section('content')
    <div class="row mb-3">
        <div class="col">
            <div class="float-start">
                <h4 class="mt-2">{{$title}}</h4>
            </div>
        </div>
        <div class="col">

        </div>
    </div>
<div class="card mt-2">
    <div class="card-body">
        <div class="row">
            <div class="col">
                <table class="table table-bordered">
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <td>{{$client->name}}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Phone Number') }}</th>
                        <td>{{$client->phone}}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Email') }}</th>
                        <td>{{$client->email}}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Address') }}</th>
                        <td>{{$client->address}}</td>
                    </tr>
                    <tr>
                        <th>{{ __('City') }}</th>
                        <td>{{$client->city ?? 'N/A'}}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Contact Person Name') }}</th>
                        <td>{{$client->contact_person_name ?? 'N/A'}}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Remarks') }}</th>
                        <td>{{$client->remarks ?? 'N/A'}}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Created At') }}</th>
                        <td>{{$client->created_at}}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Updated At') }}</th>
                        <td>{{$client->updated_at}}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Status') }}</th>
                        <td>
                            @if($client->status == 'active')
                                <span class="badge bg-success">{{ __('Active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
