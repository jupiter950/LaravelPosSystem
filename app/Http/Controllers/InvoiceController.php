<?php

namespace App\Http\Controllers;

use App\Invoice;
use App\Product;

use App\Utils\ModuleUtil;
use App\BusinessLocation;
use App\CustomerGroup;
use App\Utils\TransactionUtil;
use App\Utils\ProductUtil;
use App\Utils\BusinessUtil;
use App\TaxRate;
use Illuminate\Http\Request;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use OpenAI\Laravel\Facades\OpenAI;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class InvoiceController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $transactionUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;

        $this->dummyPaymentLine = [
            'method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return' => 0, 'transaction_no' => '',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice, $id)
    {
        $curInvoice = Invoice::findOrFail($id);

        return view('purchase.import_invoice.edit')
            ->with('curInvoice', $curInvoice);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invoice $invoice)
    {

        $data = [
            'qty' => $request->input('quantity'),
            'unit_price' => $request->input('price')
        ];

        if(Invoice::where('id', $request->input('invoice_no'))->update($data)) {
            return redirect()->back();
        }
    }

    public function updateItem(Request $request)
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $taxes = TaxRate::where('business_id', $business_id)
            ->ExcludeForTaxGroup()
            ->get();
        $orderStatuses = $this->productUtil->orderStatuses();
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

        $default_purchase_status = null;
        if (request()->session()->get('business.enable_purchase_status') != 1) {
            $default_purchase_status = 'received';
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);

        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->productUtil->payment_types(null, true, $business_id);

        //Accounts
        $accounts = $this->moduleUtil->accountsDropdown($business_id, true);

        $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];

        $data = [
            'qty' => $request->input('quantity'),
            'unit_price' => $request->input('price')
        ];

        if(Invoice::where('id', $request->input('invoice_no'))->update($data)) {
            return redirect('/purchases/create')
                ->with(compact('taxes', 'orderStatuses', 'business_locations', 'currency_details', 'default_purchase_status', 'customer_groups', 'types', 'shortcuts', 'payment_line', 'payment_types', 'accounts', 'bl_attributes', 'common_settings'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice, $id)
    {
        $product = Invoice::findOrFail($id);

        if($product->delete()) {
            return redirect()->back();
        }
    }

    public function addInvoiceItem(Request $request) {
        try {
            $invoice = new Invoice();
            $unique_invoice = $request->session()->get('invoice_no');

            $invoice->invoice_no = $unique_invoice;
            $invoice->product_id = $request->input("productID");
            $invoice->qty = $request->input("quantity");
            $invoice->unit_price = $request->input("price");

            if($invoice->save()) {
                $html = view("purchase.import_invoice.display")
                        ->with(compact('unique_invoice'))->render();
                
                return redirect()->back();
            }
        } catch(\Exception $e) {
            return [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * @desc This function is to get pages of pdf file and 
     * running the 'gs' command to convert from pdf file to png file...
     */
    private function getPdfPages($pdfPath)
    {
        $tempFolder = sys_get_temp_dir() . "/" . uniqid();

        if (!file_exists($tempFolder)) {
            mkdir($tempFolder, 0777, true);
        }

        $outputFilenamePattern = $tempFolder . '/output%d.png';

        $command = 'gswin64c -sDEVICE=png16m -o ' . escapeshellarg($outputFilenamePattern) . ' ' . escapeshellarg($pdfPath);

        exec($command, $output, $returnCode); // running gs command

        if ($returnCode !== 0) {
            dd($returnCode);
        } // exception for error...

        return $tempFolder;
    }

    public function selectProductsWithSKU($values)
{
    $matchedProducts = [];
    $unmatchedProducts = [];

    if ($values && is_array($values)) {
        $products = Product::all();

        foreach ($values as $value) {
            $name = $value['name'];
            $sku = $value['sku'];

            $matchingProduct = null;
            $maxSimilarity = 0;

            foreach ($products as $product) {
                $nameSimilarity = similar_text($name, $product->name, $namePercent);
                $skuSimilarity = similar_text($sku, $product->sku, $skuPercent);

                if ($namePercent > 95 || $skuPercent > 95) {
                    $matchingProduct = $product;
                    $maxSimilarity = max($namePercent, $skuPercent);
                    break;
                }
            }

            if ($matchingProduct) {
                $matchedProduct = [
                    'product' => $matchingProduct,
                    'qty' => $value['qty'],
                    'unit_price' => $value['unit_price']
                ];

                $matchedProducts[] = $matchedProduct;
            } else {
                $unmatchedProduct = [
                    'name' => $name, // Add the name of the unmatched item
                    'qty' => $value['qty'],
                    'unit_price' => $value['unit_price']
                ];

                $unmatchedProducts[] = $unmatchedProduct;
            }
        }
    }

    return [
        'matchedProducts' => $matchedProducts,
        'unmatchedProducts' => $unmatchedProducts
    ];
}

    /**
     * @desc This function is the process invoice pdf file and
     * get the raw data using vision api...
     */
    public function importPurchaseInvoice(Request $request)
    {
        try {
            $credentialPath = public_path() . '/credential/cloud-api-new.json';

            putenv("GOOGLE_APPLICATION_CREDENTIALS=$credentialPath"); // setting json credential file to use vision API...
    
            $imageAnnotator = new ImageAnnotatorClient();
    
            // upload a pdf file to public folder...
            $path = $request->file('file')->store('public/invoices');
            $resource = public_path() . '/uploads/' . $path;
    
            // saving a png file to temp folder...
            $tempFolder = $this->getPdfPages($resource);
            $images = scandir($tempFolder);
    
    
            $invoiceText = "";
            foreach ($images as $image) {
                if ($image != '.' && $image != '..') {
                    $imagePath = $tempFolder . '/' . $image;
    
                    $imageContent = file_get_contents($imagePath);
    
                    $response = $imageAnnotator->textDetection($imageContent);
                    $texts = $response->getTextAnnotations();
    
                    if (!empty($texts)) {
                        foreach ($texts as $text) {
                            $invoiceText .= $text->getDescription() . "\n";
                        }
                        $invoiceText .= "\n";
                    }
                }
            } // !!! extracting raw data to string....
    
            if ($invoiceText) {
                $messages = "I have the raw data of an invoice as the $invoiceText and I need to get all the items without any loss of data. Here are the important requirements:

                It's crucial to extract the table data accurately.
                Don't translate Arabic letters in English.
                Please ensure that Arabic alphabets are preserved.
                Specifically, the SKU numbers and quantity, unit prices need to be extracted for business purposes.
                If there is no SKU column, the ID field can be used as the SKU value in the table.
                If there is no 'name', 'SKU' column alphabetically, 'description' will be name. (Because all of letters might be Arabic, so you need to extract data when 'SKU' and 'description' are Arabic.). 
                I would like pairs of product names, SKUs, quantities and unit price in the format '{{name}}' => '{{sku}}' %% '{{ qty }} $$ {{ unit_price }} '. These pairs should be separated by the '||' symbol and enclosed with 2 '@@@' symbols.
                Please give me all of the pairs with that format.
                
                The desired format of the result should be as follows:
                @@@
                '{{name}}' => '{{sku}}' %% '{{ qty }}' $$ '{{ unit_price }}' ||
                '{{name}}' => '{{sku}}' %% '{{ qty }}' $$ '{{ unit_price }}' ||
                '{{name}}' => '{{sku}}' %% '{{ qty }}' $$ '{{ unit_price }}' ||
                @@@

                
                If a SKU value is missing, please use 'empty_SKU' instead. Similarly, if a product name is missing, please use 'empty_Name' instead and if a quantity is missing, please use 'empty_Qty instead.'.
                
                Thank you. " . $invoiceText;

                $result = $this->constructInvoiceWithOpenAIToJSON($messages);

                // dd($result);
    
                $products = $this->selectProductsWithSKU($result);

                // dd($existingProducts);

                $unique_invoice = uniqid();
                
                return [
                    'success' => true,
                    'msg' => __('lang_v.imported'),
                    'existingProducts' => $products['matchedProducts'],
                    'nonExistingProducts' => $products['unmatchedProducts'],
                    'unique_invoice' => $unique_invoice
                ];
            }
        } catch(\Exception $e) {
            return [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }
    }

    public function constructInvoiceWithOpenAIToJSON(string $rawData)
    {

        try {
            $messageChunks = str_split($rawData, 4096); // Split the message into smaller chunks

            $batchSize = 4; // Number of requests to send in each batch
            $numChunks = count($messageChunks);
            $numBatches = ceil($numChunks / $batchSize);

            $generatedContent = '';

            for ($batchIndex = 0; $batchIndex < $numBatches; $batchIndex++) {
                $batchStartIndex = $batchIndex * $batchSize;
                $batchEndIndex = ($batchIndex + 1) * $batchSize;
                $batchChunks = array_slice($messageChunks, $batchStartIndex, $batchSize);

                $messages = array_map(function ($chunk) {
                    return [
                        'role' => 'user',
                        'content' => $chunk
                    ];
                }, $batchChunks);

                $data = OpenAI::chat()->create([
                    'model' => 'gpt-4-1106-preview',
                    'messages' => $messages
                ]);

                foreach ($data['choices'] as $choice) {
                    $generatedContent .= $choice['message']['content'];
                }
            }

            preg_match('/<body>(.*?)<\/body>/s', $generatedContent, $matches); // getting html content...
            preg_match('/@@@(.*?)@@@/s', $generatedContent, $matches_json); // getting pairs of 'name of product' and SKU...

            // Extracting HTML content
            if (isset($matches[1])) {
                $html = $matches[1];
            } else {
                $html = "<p>No Data</p>";
            } // !! Extracting HTML content

            // Extracting pairs...
            if (isset($matches_json[1])) {
                $extractedData = $matches_json[1];

                $delimiter = "||";
                $data = explode($delimiter, $extractedData);

                $result = array();
                foreach ($data as $pair) {
                    $pair = str_replace("'", "", trim($pair));
                    if (!empty($pair)) {
                        $pair = trim($pair, "'");
                        $parts = explode("=>", $pair);
                        $name = trim($parts[0]);
                        $sku = trim(explode("%%", trim($parts[1]))[0]);

                        $parts_01 = explode("%%", $pair);
                        $qty = intval(trim(explode("$$", trim($parts_01[1]))[0]));
                        $unit_price = intval(trim(explode("$$", trim($parts_01[1]))[1]));
                        if ($name !== 'empty_Name' && $sku !== 'empty_SKU') {
                            $subarray = array(
                                'name' => $name,
                                'sku' => $sku,
                                'qty' => $qty,
                                'unit_price' => $unit_price
                            );
                            $result[] = $subarray;
                        }
                    }
                }

                $jsonResult = $result;
            } else {
                $jsonResult =  "<p>No match found.</p>";
            } // !! Extracting pairs...

            return $jsonResult;
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
        }
    }
}
