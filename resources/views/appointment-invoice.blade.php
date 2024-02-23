<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Invoice #{{$data->id}} </title>
    <style>
        body {
            font-family: Helvetica, sans-serif;
            font-size: 13px;
        }

        .container {
            max-width: 680px;
            margin: 0 auto;
        }

        .logotype {
            background: #000;
            color: #fff;
            width: 75px;
            height: 75px;
            line-height: 75px;
            text-align: center;
            font-size: 11px;
        }

        .column-title {
            background: #eee;
            text-transform: uppercase;
            padding: 15px 5px 15px 15px;
            font-size: 11px
        }

        .column-detail {
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .column-header {
            background: #eee;
            text-transform: uppercase;
            padding: 15px;
            font-size: 11px;
            border-right: 1px solid #eee;
        }

        .row {
            padding: 7px 14px;
            border-left: 1px solid #eee;
            border-right: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .alert {
            background: #ffd9e8;
            padding: 20px;
            margin: 20px 0;
            line-height: 22px;
            color: #333
        }

        .socialmedia {
            background: #eee;
            padding: 20px;
            display: inline-block
        }

        @page {
            margin: 0;
        }

        @media print {
            .hideMe {
                display: none;
            }
        }

        .noPrint {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            margin: 20px 0px;
        }
    </style>
</head>

<body>
    <div class="noPrint">
        <button onclick="window.print();" class="hideMe" style="padding: 10px;border-radius: 5px;; cursor: pointer;">
            Print Invoice
        </button>
    </div>
    <div class="container">

        <h3>Your contact details</h3>

        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td width="50%" style="background:#eee;padding:20px 5px;">
                    <strong>
                        Costumer Name
                    </strong>

                    {{$data->userInfo->first_name}} {{$data->userInfo->last_name}}
                    <br>

                </td>
                <td width="50%" style="background:#eee;padding:20px 5px;">
                    <strong> Appointment ID: </strong> #{{$data->id}} <br>
                    <strong>Appointment placed on::</strong> {{$data->appointments_to == 1 ? 'Home':'At Salon'}} <br>
                </td>
            </tr>
            <tr>

                <td width="50%" style="background:#eee;padding:20px 5px;">
                    <strong>Date:</strong> {{$data->save_date}} <br>
                    <strong>Payment type:</strong> {{$data->pay_method}}<br>
                    <strong>Service At:</strong> {{$data->appointments_to == 1 ? 'Home':'At Salon'}} <br>
                </td>
                <td style="background:#eee;padding:20px 5px;">
                    <strong>Order-no:</strong> {{$data->id}}<br>
                    <strong>E-mail:</strong> {{$general->email}} <br>
                    <strong>Phone:</strong> {{$general->mobile}}<br>
                </td>
            </tr>
        </table><br>
        <h3>Salon / Beautician Information</h3>
        <table width="100%">
            <tr>
                <td width="100%">
                    Business Name:
                    @if($data->freelancer_id == 0)
                    <strong>{{$data->salonInfo->name}}</strong> <br>
                    @endif
                    @if($$data->salon_id == 0)
                    <strong>{{$data->individualInfo->first_name}} {{$data->individualInfo->last_name}}</strong> <br>
                    @endif
                    Email: <strong>{{$data->ownerInfo->email}}</strong><br />
                    Phone Number: <strong>{{$data->ownerInfo->mobile}}</strong>
                    <br>
                    @if($data->freelancer_id == 0)
                    Address: <strong>{{$data->salonInfo->address}}</strong> <br />
                    @endif
                </td>
                @endif
            </tr>
        </table>
        <h3>Appointment details</h3>
        <table width="100%">
            <td width="50%" style="background:#eee;padding:20px 5px;">
                Appointment place:
                <strong>
                    {{$data->address}}
                </strong><br />
            </td>
            <td width="50%" style="background:#eee;padding:20px 5px;">
                Appointment Date/Time: <strong>
                    {{$data->save_date}}
                </strong><br />
            </td>
        </table>

        <h3>Your Treatments</h3>

        <table width="100%" style="border-collapse: collapse;border-bottom:1px solid #eee; display:flex;">


            @if($data->items->services)
            <tr width="50%">
                @foreach($data->items->services as $services)
                <td class="row">
                    <span style="color:#777;font-size:11px;">#{{$services->id}}</span><br>{{$services->name}}
                </td>
                <!-- <td class="row">
                    @if($services->discount > 0)
                        {{$general->currencySymbol}} {{$services->off}}
                    @endif
                    @if($services->discount <= 0)
                        {{$general->currencySymbol}} {{$services->price}}
                    @endif
                    </td> -->
            </tr>
            @endforeach

            @endif

            <!--      @if($data->items->packages)
            @foreach($data->items->packages as $services)
            <tr>
                <td class="row"><span
                        style="color:#777;font-size:11px;">#{{$services->id}}</span><br>{{$services->name}}
                    @foreach($services->services as $sub)
                    <p
                        style="margin: 0px; line-height: 140%; text-align: start; word-wrap: break-word; font-family: 'Montserrat',sans-serif; font-size: 8px;">
                        - {{$sub->name}}
                    </p>
                    @endforeach
                </td>
            </tr>
            @endforeach

            @endif -->

        </table>
        <h3>Your Treatments</h3>
        <table width="100%">
            <td width="50%">
                Treatment(s) List:
                @if($data->items->services)
                @foreach($data->items->services as $services)
                <div class="row">
                    <span style="color:#777;font-size:11px;">#{{$services->id}}</span><br>{{$services->name}}
                </div>
                @endforeach
                @endif
            </td>
            <td width="50%">
                Appointment Date/Time: <strong>
                    {{$data->save_date}}
                </strong><br />
            </td>
        </table>
        <table width="100%" style="background:#eee;padding:20px;">
            <!--   <tr>
                <td>
                    <table width="300px" style="float:right"> -->
            <tr>
                <td><strong>Service Cost:</strong></td>
                <td style="text-align:right"> {{$general->currencySymbol}} {{$data->total}} </td>
            </tr>
            <tr>
                <td><strong>Distance Charge:</strong></td>
                <td style="text-align:right"> {{$general->currencySymbol}} {{$data->appoint_distance}} </td>
            </tr>
            <tr>
                <td><strong>Total Discount:</strong></td>
                <td style="text-align:right"> - {{$general->currencySymbol}} {{$data->discount}} </td>
            </tr>
            <tr>
                <td><strong>Sub-total:</strong></td>
                <td style="text-align:right">{{$general->currencySymbol}} {{$data->appoint_subtotal}} </td>
            </tr>
            <tr>
                <td><strong>Deposit fee:</strong></td>
                <td style="text-align:right">{{$general->currencySymbol}} {{$data->appoint_deposit_fee}}
                </td>
            </tr>
            <tr>
                <td><strong>Booking fee:</strong></td>
                <td style="text-align:right">{{$general->currencySymbol}} {{$general->booking_fee}} </td>
            </tr>
            <tr>
                <td><strong>Processing fee:</strong></td>
                <td style="text-align:right">{{$general->currencySymbol}} {{$general->processing_fee}} </td>
            </tr>
            <tr>
                <td><strong>Grand total by Earnings:</strong></td>
                <td style="text-align:right">{{$general->currencySymbol}} {{$data->appoint_subtotal -
                    $data->appoint_deposit_fee}} </td>
            </tr>
            <!--    </table>
                </td>
            </tr> -->
        </table>
        <br>

    </div><!-- container -->
</body>

</html>
