<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pdf View</title>
    </head>
    <style>
        table tr td{
            vertical-align: baseline;
        }
        body{
            font-family: Arial, Helvetica, sans-serif;
        }
    </style>
    @php
    $dollor = '$'; 
    if(!empty($estimate->currency)){
        $dollor = $estimate->currency->currency_symbol; 
    }
    @endphp
    <body style="max-width: 700px; width: 100%; margin: auto;">
        <table style="width: 100%;">
            <tr>
                <td colspan="2" style="font-weight: 700; font-size: 1rem; text-align: right; padding: 0.5rem; color: #000;">Estimate No: <span>{{ $estimate->estimate_type == 'estimate' ? 'EST' : 'PRO' }}{{$estimate->estimate_number}}</span></td>
            </tr>
            <tr>
                <td colspan="2" style="font-weight: 700; font-size: 1rem; text-align: right; padding: 0.5rem; color: #000;">Estimate Date: <span>{{$estimate->date_of_issue}}</span></td>
            </tr>
            <tr>
                <td style="font-weight: 700; font-size: 1rem; padding: 0.5rem; color: #000;">From</td>
                <td style="font-weight: 700; font-size: 1rem; text-align: right; padding: 0.5rem; color: #000;">To</td>
            </tr>
            <tr>
                <td style="font-weight: 400; font-size: 1rem; padding: 0.5rem; color: #000;">{{$estimate->from_company_name}} {{$estimate->from_address1}}, {{$estimate->from_address2}}</td>
                <td style="font-weight: 400; font-size: 1rem; text-align: right; padding: 0.5rem; color: #000;">{{$estimate->to_client_name}} {{$estimate->to_address1}}, {{$estimate->to_address2}}</td>
            </tr>
            @if(!empty($estimate->estimateSection))
            @foreach($estimate->estimateSection as $section)
            <tr>
                <td style="font-size: 1rem; font-weight: 700; padding: 0.5rem; color: #000;">{{$section->section_name}}</td>
            </tr>
            <tr>
                <td colspan="2" style="font-size: 1rem; font-weight: 400; padding: 0.5rem; color: #000;">{{$section->section_text}}</td>
            </tr>
            @endforeach
            @endif
            <tr>
                <td colspan="2" style="font-size: 1rem; font-weight: 700; padding: 0.5rem; color: #000;">Pricing</td>
            </tr>
            <tr>
            <table style="width: 100%;" width="100%">
                <tr>
                    <th style="font-weight: 700; font-size: 1rem; text-align: left; border-top: 1px solid #ddd; padding: 0.5rem; color: #000;">Description</th>
                    <th style="font-weight: 700; font-size: 1rem; text-align: left; border-top: 1px solid #ddd; padding: 0.5rem; color: #000;">Rate</th>
                    <th style="font-weight: 700; font-size: 1rem; text-align: left; border-top: 1px solid #ddd; padding: 0.5rem; color: #000;">Qty</th>
                    <th style="font-weight: 700; font-size: 1rem; text-align: left; border-top: 1px solid #ddd; padding: 0.5rem; color: #000;">Line Total</th>
                </tr>
                @if(!empty($estimate->items))
                @foreach($estimate->items as $item)
                <tr>
                    <td style="font-weight: 400; font-size: 1rem; border-bottom: 1px solid #ddd; padding: 0.5rem; color: #000;">{{$item->item_name}}</td>
                    <td style="font-weight: 400; font-size: 1rem; border-bottom: 1px solid #ddd; padding: 0.5rem; color: #000;">{{$dollor}}{{$item->unit_price}}</td>
                    <td style="font-weight: 400; font-size: 1rem; border-bottom: 1px solid #ddd; padding: 0.5rem; color: #000;">{{$item->quantity}}</td>
                    <td style="font-weight: 400; font-size: 1rem; border-bottom: 1px solid #ddd; padding: 0.5rem; color: #000;">{{$dollor}}{{$item->amount}}</td>
                </tr>
                @endforeach
                @endif
                <tr>
                    <td colspan="3" style="padding: 0.5rem; font-weight: 700; font-size: 1rem; color: #000;">Subtotal</td>
                    <td style="padding: 0.5rem; font-weight: 700; font-size: 1rem; color: #000;">{{$dollor}}{{$estimate->sub_total}}</td>
                </tr>
                @if($estimate->house_tax_total > 0 && $estimate->house_tax_total !='')
                <tr>
                    <td colspan="3" style="padding: 0.5rem; font-weight: 700; font-size: 1rem; color: #000;">House Service ({{$estimate->houseService->tax_rate}}%)</td>
                    <td style="padding: 0.5rem; font-weight: 700; font-size: 1rem; color: #000;">{{$dollor}}{{$estimate->house_tax_total}}</td>
                </tr>
                @endif
                @if(!empty($estimate->items))
                @foreach($estimate->items as $taxItem)
                @if(!empty($taxItem->TaxInfo))
                <tr>
                    <td colspan="3" style="padding: 0.5rem; font-weight: 700; font-size: 1rem; color: #000;">Tax ({{$taxItem->TaxInfo->rate_percent}}%)</td>
                    <td colspan="3" style="padding: 0.5rem; font-weight: 700; font-size: 1rem; color: #000;">{{$dollor}}{{$taxItem->tax_amount}}</td>
                </tr>
                @endif
                @endforeach
                @endif
                <tr>
                    <td colspan="3" style="padding: 0.5rem; font-weight: 700; font-size: 1rem; color: #000;">Total Amount</td>
                    <td colspan="3" style="padding: 0.5rem; font-weight: 700; font-size: 1rem; color: #000;">{{$dollor}}{{$estimate->total}}</td>
                </tr>
            </table>
      </tr>
        </table>
    </body>
</html>