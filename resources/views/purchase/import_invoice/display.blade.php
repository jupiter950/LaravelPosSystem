<!-- Main content -->
<section class="content no-print">

	<div class="form-group">
		<div>
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fa fa-search"></i>
				</span>
				{!! Form::text('search_product', null, [
					'class' => 'form-control mousetrap',
					'id' => 'search_product_to_add',
					'placeholder' => __('lang_v1.search_product_placeholder'),
					'disabled' => false,
				]) !!}
			</div>
			<div>
				<input type="text" name="" id="add_invoice_item">
				<input type="number" name="" id="">
				<input type="number" name="" id="">
			</div>
			<button type="button" class="btn btn-primary">
			Add
		</button>
		</div>
	</div>
	<div class="p-8">
		<p style="font-size: 20px; text-align:center;">Selected products from Database</p>
		<div class="row">
			<div class="col-sm-12">
				<div class="table-responsive">
					<table class="table table-condensed table-bordered table-th-green text-center table-striped"
						id="purchase_invoice_entry_table">
						<thead>
							<tr>
								<th>#</th>
								<th>@lang( 'product.product_name' )</th>
								<th>SKU</th>
								<th>@lang( 'purchase.purchase_quantity' )</th>
								<th>Unit Price</th>
								<th>Total</th>
								<th style="width: 200px;">Action</th>
								<th><i class="fa fa-trash" aria-hidden="true"></i></th>
							</tr>
						</thead>
						@if(isset($unique_invoice))
						<tbody>
							@foreach (\App\Invoice::where('invoice_no', '=', $unique_invoice)->get() as $invoice_item)
							<tr data-row-id="{{ $invoice_item->id }}">
								<td></td>
								<td>
									@if (\App\Product::where('id', '=', $invoice_item->product_id)->first()->type ==
									'variable')
									<!-- {{ \App\Product::where('id', '=', $invoice_item->product_id)->first()->name . ' - ' . \App\Variation::where('product_id', '=',
											$product["id"])->first()->name . ' ( ' . \App\Variation::where('product_id', '=',
											$product["id"])->first()->sub_sku . ' ) ' }} -->
									11
									@else
									{{ \App\Product::where('id', '=', $invoice_item->product_id)->first()->name }}
									@endif
								</td>
								<td>{{ \App\Product::where('id', '=', $invoice_item->product_id)->first()->sku }}</td>
								<td><input type="number" name="quantity" class="form-control" id="" value={{
										$invoice_item->qty }} disabled></td>
								<td><input type="number" name="unit_price" class="form-control" id="" value={{
										$invoice_item->unit_price }} disabled></td>
								<td>{{ $invoice_item->qty * $invoice_item->unit_price }}</td>
								<td>
									<ul class="list-inline m-0">
										<li class="list-inline-item">
											<button class="btn btn-success btn-sm rounded-0 btn-edit" data-row-id="{{ $invoice_item->id }}" type="button" data-toggle="tooltip" data-placement="top" title="Edit"><i class="fa fa-edit"></i></button>
										</li>
										<li class="list-inline-item">
											<button class="btn btn-danger btn-sm rounded-0" type="button" data-toggle="tooltip" data-placement="top" title="Delete"><i class="fa fa-trash"></i></button>
										</li>
									</ul>
								</td>
							</tr>
							@endforeach
						</tbody>
						@endif
					</table>
				</div>
				<hr />
				<div class="pull-right col-md-5">
					<table class="pull-right col-md-12">
						<tr>
							<th class="col-md-7 text-right">@lang( 'lang_v1.total_items' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_quantity" class="display_currency" data-currency_symbol="false"></span>
							</td>
						</tr>
						<tr class="hide">
							<th class="col-md-7 text-right">@lang( 'purchase.total_before_tax' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_st_before_tax" class="display_currency"></span>
								<input type="hidden" id="st_before_tax_input" value=0>
							</td>
						</tr>
						<tr>
							<th class="col-md-7 text-right">@lang( 'purchase.net_total_amount' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_subtotal" class="display_currency"></span>
								<!-- This is total before purchase tax-->
								<input type="hidden" id="total_subtotal_input" value=0 name="total_before_tax">
							</td>
						</tr>
					</table>
				</div>
				<button class="btn btn-primary">Save</button>
				<input type="hidden" id="row_count" value="0">
			</div>
		</div>
	</div>
</section>