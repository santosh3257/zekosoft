<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice</title>
</head>

<body style="max-width: 700px; width: 100%; margin: auto;">
    <table style="width: 100%; border-collapse: collapse;table-layout: fixed;">
        <tr>
            <td>
                <span>
                    <img src="https://uilogos.co/img/logotype/circle.png" alt="Logo" style="width: 5rem;" />
                </span>
            </td>
            <td style="font-size: 14px; font-weight: 400; color: #000; text-align: right;">Invoice Number: <span style="font-size: 14px; font-weight: 400; color: #000; text-align: right;">{{$invoice->invoice_number}}</span></td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 14px; font-weight: 400; color: #000;  text-align: right;">Issued: <span style="font-size: 14px; font-weight: 400; color: #000; text-align: right;">{{$invoice->issue_date->format('d/m/Y')}}</span></td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 14px; font-weight: 400; color: #000;  text-align: right;">OCR No: <span style="font-size: 14px; font-weight: 400; color: #000; text-align: right;">{{$invoice->ocr_number}}</span></td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 14px; font-weight: 400; color: #000; text-align: right;">Reference: <span style="font-size: 14px; font-weight: 400; color: #000; text-align: right;">{{$invoice->reference}}</span></td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 14px; font-weight: 400; color: #000; padding-bottom: 0.50rem; text-align: right;">Reverse Charge</td>
        </tr>
        <tr>
            <td style="font-size: 1rem; font-weight: 600; color: #000; padding: 0.25rem; text-align: left;">Invoice From</td>
            <td style="font-size: 1rem; font-weight: 600; color: #000; padding: 0.25rem; text-align: right;">Invoice To</td>
        </tr>
        <tr>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: left;">Company Name</td>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: right;">Company Name</td>
        </tr>
        <tr>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: left;">{{$invoice->from_company_name}}</td>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: right;">{{$invoice->to_client_name}}</td>
        </tr>
        <tr>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: left;">{{$invoice->from_address1}}</td>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: right;">{{$invoice->to_address1}}</td>
        </tr>
        <tr>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: left;">{{$invoice->from_address2}}</td>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: right;">{{$invoice->to_address2}}</td>
        </tr>
        <tr>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: left; padding-bottom: 2rem;">{{$invoice->from_phone}}</td>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: right; padding-bottom: 2rem;">{{$invoice->to_phone}}</td>
        </tr>
        <tr>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: left;">Organisation No: {{$invoice->company->origination_number}}</td>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: right;">Organisation No:</td>
        </tr>
        <tr>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: left;">VAT Number: {{$invoice->company->vat_number}}</td>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: right;">VAT Number:</td>
        </tr>
        <tr>
            <td style="width: 50%; font-size: 12px; font-weight: 300; color: #000; text-align: left;">
                <tr>
                    <td style="float: left; width: 50%; font-size: 12px; font-weight: 300; color: #000; text-align: left;">Bankgiro: {{$invoice->company->bankgiro}}</td>
                    <td style="float: left; width: 50%; font-size: 12px; font-weight: 300; color: #000; text-align: left;">plusgiro: {{$invoice->company->plusgiro}}</td>
                </tr>
                <tr>
                    <td style="float: left; width: 50%; font-size: 12px; font-weight: 300; color: #000; text-align: left;">IBAN: {{$invoice->company->iban}}</td>
                    <td style="float: left; width: 50%; font-size: 12px; font-weight: 300; color: #000; text-align: left;">BIC: {{$invoice->company->bic}}</td>
                </tr>
            </td> 
        </tr>
        <tr>
            <td style="font-size: 12px; font-weight: 300; color: #000; text-align: left; padding-bottom: 2rem;">{{$invoice->company->fskatt == 'approved' ? 'Approved for F-skatt' : 'Not approved for F-skatt'}}</td>
        </tr>
    </table>
    <table style="width: 100%;border-collapse: collapse; table-layout: fixed;">
        <thead>
            <tr>
                <th style="font-size: 14px; font-weight: bold; color: #000; text-align: left;">Article</th>
                <th style="font-size: 14px; font-weight: bold; color: #000; text-align: left;">House Work</th>
                <th style="font-size: 14px; font-weight: bold; color: #000; text-align: left;">Quantity</th>
                <th style="font-size: 14px; font-weight: bold; color: #000; text-align: left;">Unit</th>
                <th style="font-size: 14px; font-weight: bold; color: #000; text-align: left;">Rate</th>
                <th style="font-size: 14px; font-weight: bold; color: #000; text-align: left;">Discount</th>
                <th style="font-size: 14px; font-weight: bold; color: #000; text-align: left;">Amount</th>
                <th style="font-size: 14px; font-weight: 500; color: #000; text-align: right;">VAT</th>
            </tr>
        </thead>
        <tbody>
        @php
        $subTotal = 0;
        $totalTax = 0;
        @endphp
        @foreach($invoice->items as $item)
        @php
        $subTotal = $subTotal + $item->amount;
        $totalTax = $totalTax + $item->tax_amount;
        $total = $subTotal + $totalTax;
        @endphp
            <tr>
                <td style="font-size: 14px; font-weight: 400; color: #000; text-align: left;">{{$item->item_name}}</td>
                <td style="font-size: 14px; font-weight: 400; color: #000; text-align: left;">{{$item->houseWork ? $item->houseWork->work_name : ''}} </td>
                <td style="font-size: 14px; font-weight: 400; color: #000; text-align: left;">{{$item->quantity}}</td>
                <td style="font-size: 14px; font-weight: 400; color: #000; text-align: left;">{{$item->unit}}</td>
                <td style="font-size: 14px; font-weight: 400; color: #000; text-align: left;">${{$item->unit_price}}</td>
                <td style="font-size: 14px; font-weight: 400; color: #000; text-align: left;">{{$invoice->discount_percentage ? $invoice->discount_percentage : '0'}}%</td>
                <td style="font-size: 14px; font-weight: 400; color: #000; text-align: left;">${{$item->amount}}</td>
                <td style="font-size: 14px; font-weight: 400; color: #000; text-align: right;">{{$item->TaxInfo->percentage}}%</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="7" style="font-size: 14px; font-weight: 400; color: #000; padding-top: 1rem; text-align: right;">Subtotal</td>
                <td style="font-size: 14px; font-weight: 400; color: #000; padding-top: 1rem; text-align: right;">${{$subTotal}}</td>
            </tr>
            <tr>
                <td colspan="7" style="font-size: 14px; font-weight: 400; color: #000; padding-top: 0.25rem; text-align: right;;">House Work</td>
                <td style="font-size: 14px; font-weight: 400; color: #000; padding-top: 0.25rem; text-align: right;;">${{$invoice->house_tax_total ? $invoice->house_tax_total : '0.00'}}</td>
            </tr>
            <tr>
                <td colspan="7" style="font-size: 14px; font-weight: 400; color: #000; padding-top: 0.25rem; text-align: right;;">Discount</td>
                <td style="font-size: 14px; font-weight: 400; color: #000; padding-top: 0.25rem; text-align: right;;">${{$invoice->discount ? $invoice->discount : '0.00'}}</td>
            </tr>
            @foreach($invoice->items as $item)
            <tr>
                <td colspan="7" style="font-size: 14px; font-weight: 400; color: #000; padding-top: 0.25rem; text-align: right;;">Tax({{$item->TaxInfo->percentage}}%)</td>
                <td style="font-size: 14px; font-weight: 400; color: #000; padding-top: 0.25rem; text-align: right;;">${{$item->tax_amount ? $item->tax_amount : '0.00'}}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="7" style="font-size: 14px; font-weight: 500; color: #000; padding-top: 1rem; text-align: right; padding-bottom: 1rem;">Total Amount</td>
                <td style="font-size: 14px; font-weight: 500; color: #000; padding-top: 1rem; padding-bottom: 1rem; text-align: right;">${{($total)-($invoice->discount+$invoice->house_tax_total)}}</td>
            </tr>
        </tbody>
    </table>
    <table style="width: 100%;border-collapse: collapse; table-layout: fixed;">
    <tr><td><span style="font-size: 18px; font-weight: 500; color: #000; text-align: left;">Note:</span></td></tr>
    <tr style="padding-top: 0.75rem;"><td><span style="font-size: 14px; font-weight: 400; color: #000; text-align: left;">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text.</span></td></tr>
    </table>

</body>

</html>