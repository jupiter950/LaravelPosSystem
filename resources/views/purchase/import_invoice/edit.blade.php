@extends('layouts.app')
@section('title', __('purchase.purchases'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>Edit a product information</h1>
</section>

<!-- Main content -->
<section class="content no-print">
    
    <form
        method="POST"
        action="{{ route('update_invoice_item') }}"
    >
        @method('PUT')
        @csrf
        <div class="form-group">
            <label for="name">Product Name</label>
            <input 
                type="text" 
                class="form-control" 
                id="name" 
                aria-describedby="for_name" 
                placeholder="Product Name..." 
                disabled 
                value={{\App\Product::where('id', '=', $curInvoice->product_id)->first()->name}}
            >
            <small id="for_name" class="form-text text-muted">You can't change the name.</small>
        </div>
        <input type="hidden" name="invoice_no" value={{ $curInvoice->id }}>

        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" class="form-control" id="quantity" name="quantity" aria-describedby="for_quantity" placeholder="" value={{ $curInvoice->qty }}>
            <small id="for_quantity" class="form-text text-muted">Please update the quantity</small>
        </div>
        <div class="form-group">
            <label for="price">Price</label>
            <input type="number" class="form-control" id="price" name="price" aria-describedby="for_price" placeholder="" value={{ $curInvoice->unit_price }}>
            <small id="for_price" class="form-text text-muted">Please update the unit price</small>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</section>

<section id="receipt_section" class="print_section"></section>

<!-- /.content -->
@stop