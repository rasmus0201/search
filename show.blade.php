@extends('Admin.layouts.auth')

@section('title', 'Bestilling - ' . $order->displayId() . ' informationer')

@section('actions')
    <a href="{{ route('Admin.orders.index') }}" class="btn btn-primary mr-2"><i class="fal fa-chevron-left"></i></a>
@endsection

@section('content')

@php($paymentMethod = $order->paymentMethod)
@php($orderStatus = $order->orderStatus)
@php($paymentStatus = $order->paymentStatus)
@php($customer = $order->customer)
@php($shipping = $order->shippingMethod)
@php($deliveryDate = $order->deliveryDate)
@php($pickupDate = $order->pickupDate)

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                @include('Admin.orders.includes.general', ['edit' => true])
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                @include('Admin.orders.includes.totals')
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('Admin.orders.email', $order->id) }}" method="post">
                    <div class="form-row">
                        <div class="col-auto flex-grow">
                            <select class="form-control form-control-sm" name="mailable">
                                <option value="">Send email</option>
                                @foreach ($types['OrderEmail']::toSelectArray() as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            {{ csrf_field() }}
                            <button class="btn btn-sm btn-primary" type="submit" name="submit">
                                <i class="fal fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <hr>
                <form action="{{ route('Admin.orders.update', $order->id) }}" method="post">
                    <div class="form-group form-row">
                        <div class="col">
                            <select class="form-control form-control-sm" name="order_status_id">
                                <option value="">Opdater ordrestatus:</option>
                                @foreach ($orderStatuses as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <div class="col">
                            <select class="form-control form-control-sm" name="payment_status_id">
                                <option value="">Opdater betalingsstatus:</option>
                                @foreach ($paymentStatuses as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        {{ csrf_field() }}
                        <div class="col-md-6 offset-md-6 text-right">
                            <button class="btn btn-sm btn-primary" type="submit" name="submit">
                                Opdater <i class="fal fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <hr>
                <form action="{{ route('Admin.orders.destroy', $order->id) }}" method="post">
                    <div class="row">
                        {{ csrf_field() }}
                        @method('DELETE')
                        <div class="col-md-6 offset-md-6 text-right">
                            <button class="btn btn-sm btn-danger" type="submit" name="trash">
                                Papirkurv <i class="fal fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @if ($order->isQuickpay())
            <div class="card mt-4">
                <div class="card-body card-quickpay" data-order-id="{{ $order->id }}" data-order-status="{{ $orderStatus->slug }}" data-payment-id="{{ $order->payment_id }}">
                    <p class="font-weight-bold">Quickpay</p>
                    <div class="d-flex justify-content-center">
                        <div data-qp-loader class="spinner-grow text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="card mt-4">
            <div class="card-body">
                @php($history = $order->orderHistory()->orderBy('created_at', 'DESC')->get())
                @if (count($history) > 3)
                    <button class="btn btn-block btn-primary" type="button" data-toggle="collapse" data-target="#collapsable" aria-expanded="false" aria-controls="collapseExample">
                        Åben/Luk historik
                    </button>
                    <div class="collapse" id="collapsable">
                        @include('Admin.orders.includes.history')
                    </div>
                @else
                    @include('Admin.orders.includes.history')
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
