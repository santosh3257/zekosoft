<?php

namespace Modules\RestAPI\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\AcceptEstimate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Helper\Files;
use Validator;
use Storage;
use PDF;

class PublicUrlController extends ApiBaseController {

    public function viewEstimateProposal(Request $request){
        $estimate = Estimate::with('estimateSection','houseService','vatTypes','items','items.product','items.houseWork','items.TaxInfo','items.accountCode','acceptEstimate')->where('hash', $request->hash)->firstOrFail();
        $data = array(
            "status" => true,
            'estimate' => $estimate
            
        );
        return Response()->json($data, $this->successStatus);
    }

    // Upload Signature and status into the database
    public function acceptEstimateProposal(Request $request){
        DB::beginTransaction();
        if($request->status == 'accepted'){
            $validator = Validator::make($request->all(), [ 
                'full_name' => 'required',
                'email' => 'required|email',
                "status" => 'required|in:accepted,cancelled',
                "hash" => 'required',
            ]);
        }
        else{
            $validator = Validator::make($request->all(), [
                "status" => 'required|in:accepted,cancelled',
                "hash" => 'required',
            ]);
        }
        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }
        $estimateData = Estimate::where('hash', $request->hash)->first();
        if($estimateData){
           $estimate = Estimate::with('sign')->findOrFail($estimateData->id);
            $company = $estimate->company;
            if($request->status == 'accepted'){
            $accept = AcceptEstimate::firstOrNew(['estimate_id' =>  $estimateData->id]);
            $accept->company_id = $estimate->company->id;
            $accept->full_name = $request->full_name;
            $accept->estimate_id = $estimate->id;
            $accept->email = $request->email;
            $imageName = null;
            if ($request->e_sign) {
                $image = $request->signature;  // your base64 encoded
                $image = str_replace('data:image/png;base64,', '', $image);
                $image = str_replace(' ', '+', $image);
                $imageName = str_random(32) . '.' . 'jpg';
    
                Files::createDirectoryIfNotExist('estimate/accept');
    
                File::put(public_path() . '/' . Files::UPLOAD_FOLDER . '/estimate/accept/' . $imageName, base64_decode($image));
                Files::uploadLocalFile($imageName, 'estimate/accept', $estimate->company_id);
            }
            else {
                Files::createDirectoryIfNotExist('estimate/accept');
                $imageName = Files::uploadLocalOrS3($request->signature, 'estimate/accept/', 300);
            }
            $accept->signature = $imageName;
            $accept->save();
            }
            $estimate->status = $request->status;
            $estimate->saveQuietly();
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'Estimate status change successfully'
                
            ); 
            return Response()->json($data, $this->successStatus);

        }
        else{
            $data = array(
                "status" => false,
                'estimate' => "No data found"
                
            );
            return Response()->json($data, $this->successStatus);
        }
        
    }

    // Download Estimate/Proposal PDF
    public function downloadEstimatePDF($id){
        $estimate = Estimate::with('estimateSection','houseService','vatTypes','items','items.product','items.houseWork','items.TaxInfo','items.accountCode','acceptEstimate')->where('id',$id)->first();
        if($estimate){
            //return view('estimates.pdf.download-invoice', $estimate)->render();
            $pdf = PDF::loadView('estimates.pdf.download-invoice', compact('estimate'));
            $orderPdfLink = 'estimate'.$id.'.pdf';
            //Storage::put('public/estimate/'.$orderPdfLink, $pdf->output());
            $pdf->save(public_path('user-uploads/estimate/'.$orderPdfLink));
            $url = url('user-uploads/estimate/'.$orderPdfLink);
            $data = array(
                "status" => true,
                'estimate' => $url
                
            );
            return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'No data found'
                
            );
            return Response()->json($data, $this->successStatus);
        }
    }
    // Download Invoice PDF
    public function downloadInvoicePDF($id){
        /* $invoice = Invoice::where('id',$id)->first(); */
       $invoice = Invoice::with('houseService','items.product','items.houseWork','items.TaxInfo','items.accountCode')->where('id',$id)->first();
        /* return $invoice; */
        if($invoice){
            //return view('estimates.pdf.download-invoice', $estimate)->render();
            $pdf = PDF::loadView('invoices.pdf.invoice_pdf_download', compact('invoice'));
            $orderPdfLink = 'invoice'.$id.'.pdf';
            //Storage::put('public/estimate/'.$orderPdfLink, $pdf->output());
            Files::createDirectoryIfNotExist('invoice');
            $pdf->save(public_path('user-uploads/invoice/'.$orderPdfLink));
            $url = url('user-uploads/invoice/'.$orderPdfLink);
            $data = array(
                "status" => true,
                'invoice' => $url
                
            );
            return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'No data found'
                
            );
            return Response()->json($data, $this->successStatus);
        }
    }

}
