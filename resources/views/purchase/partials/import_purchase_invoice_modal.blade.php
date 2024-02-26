<style>
	#invoice-processor {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		z-index: 1;
	}

	.lds-roller {
		display: inline-block;
		position: relative;
		width: 80px;
		height: 80px;
	}

	.lds-roller div {
		animation: lds-roller 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
		transform-origin: 40px 40px;
	}

	.lds-roller div:after {
		content: " ";
		display: block;
		position: absolute;
		width: 7px;
		height: 7px;
		border-radius: 50%;
		background: #fff;
		margin: -4px 0 0 -4px;
	}

	.lds-roller div:nth-child(1) {
		animation-delay: -0.036s;
	}

	.lds-roller div:nth-child(1):after {
		top: 63px;
		left: 63px;
	}

	.lds-roller div:nth-child(2) {
		animation-delay: -0.072s;
	}

	.lds-roller div:nth-child(2):after {
		top: 68px;
		left: 56px;
	}

	.lds-roller div:nth-child(3) {
		animation-delay: -0.108s;
	}

	.lds-roller div:nth-child(3):after {
		top: 71px;
		left: 48px;
	}

	.lds-roller div:nth-child(4) {
		animation-delay: -0.144s;
	}

	.lds-roller div:nth-child(4):after {
		top: 72px;
		left: 40px;
	}

	.lds-roller div:nth-child(5) {
		animation-delay: -0.18s;
	}

	.lds-roller div:nth-child(5):after {
		top: 71px;
		left: 32px;
	}

	.lds-roller div:nth-child(6) {
		animation-delay: -0.216s;
	}

	.lds-roller div:nth-child(6):after {
		top: 68px;
		left: 24px;
	}

	.lds-roller div:nth-child(7) {
		animation-delay: -0.252s;
	}

	.lds-roller div:nth-child(7):after {
		top: 63px;
		left: 17px;
	}

	.lds-roller div:nth-child(8) {
		animation-delay: -0.288s;
	}

	.lds-roller div:nth-child(8):after {
		top: 56px;
		left: 12px;
	}

	@keyframes lds-roller {
		0% {
			transform: rotate(0deg);
		}

		100% {
			transform: rotate(360deg);
		}
	}

	.darken-effect {
		background-color: rgba(0, 0, 0, 0.8);
		/* Adjust the opacity as needed */
	}

	.blur-effect {
		filter: blur(1px);
		/* Adjust the blur radius as needed */
	}
</style>

<div class="modal fade" tabindex="-1" role="dialog" id="import_purchase_invoice_modal">
	<div class="modal-dialog modal-lg" role="document" style="width:90%;">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Import Invoice</h4>
			</div>
			<div class="modal-body" id="modal-stage" style="position: relative; ">
				
				<div class="row" style="position: relative;">
					<div id="invoice-processor">
						<div class="lds-roller">
							<div></div>
							<div></div>
							<div></div>
							<div></div>
							<div></div>
							<div></div>
							<div></div>
							<div></div>
						</div>
					</div>
					<div class="col-md-12">
						<strong>@lang( 'product.file_to_import' ):</strong>
					</div>
					<div class="col-md-12">
						<div id="import_invoice_dz" class="dropzone"></div>
					</div>
				</div>

				<div id="item-adding-form" style="margin-top: 20px;">
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-search"></i>
						</span>
						{!! Form::text('search_product', null, [
						'class' => 'form-control mousetrap',
						'id' => 'search_product_to_add_01',
						'placeholder' => __('lang_v1.search_product_placeholder'),
						'disabled' => false,
						]) !!}
					</div>
					<!-- <div class="form-group">
						<div>
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fa fa-search"></i>
								</span>
								{!! Form::text('search_product', null, [
								'class' => 'form-control mousetrap',
								'id' => 'search_product_to_add_01',
								'placeholder' => __('lang_v1.search_product_placeholder'),
								'disabled' => false,
								]) !!}
							</div>
							<div>
								<input type="text" name="" id="add_invoice_item_01">
								<input type="number" name="" id="add_invoice_item_sku_01">
								<input type="number" name="" id="add_invoice_item_qty_01">
								<input type="number" name="" id="add_invoice_item_price_01">
							</div>
							<button type="button" class="btn btn-primary" id="new_item_add_btn_01">
								Add
							</button>
						</div>
					</div> -->
				</div>

				<div style="margin-top: 24px;">
					<table id="purchase_invoice_entry_table_01"
						class="table table-condensed table-bordered table-th-green text-center table-striped"
						
					>
						<thead>
							<tr>
								<th>#</th>
								<th>@lang('product.product_name')</th>
								<th>@lang('purchase.purchase_quantity')</th>
								<th>@lang('lang_v1.unit_cost_before_discount')</th>
								<th>@lang('lang_v1.discount_percent')</th>
								<th>@lang('purchase.unit_cost_before_tax')</th>
								<th class="{{ $hide_tax }}">@lang('purchase.subtotal_before_tax')</th>
								<th class="{{ $hide_tax }}">@lang('purchase.product_tax')</th>
								<th class="{{ $hide_tax }}">@lang('purchase.net_cost')</th>
								<th>@lang('purchase.line_total')</th>
								<th class="@if (!session('business.enable_editing_product_from_purchase')) hide @endif">
									@lang('lang_v1.profit_margin')
								</th>
								<th>
									@lang('purchase.unit_selling_price')
									<small>(@lang('product.inc_of_tax'))</small>
								</th>
								@if (session('business.enable_lot_number'))
								<th>
									@lang('lang_v1.lot_number')
								</th>
								@endif
								@if (session('business.enable_product_expiry'))
								<th>
									@lang('product.mfg_date') / @lang('product.exp_date')
								</th>
								@endif
								<th style="width: 100px"><span class="glyphicon glyphicon-cog"></span></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>

			</div>
			<div class="modal-footer">
				<button class="btn btn-primary" type="button" id="import_purchase_invoice_confirm">
					Confirm
				</button>
				<button class="btn btn-primary" type="button" id="import_purchase_invoice">
					@lang( 'lang_v1.import' )
				</button>
				<button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close'
					)</button>
			</div>
		</div>
	</div>
</div>