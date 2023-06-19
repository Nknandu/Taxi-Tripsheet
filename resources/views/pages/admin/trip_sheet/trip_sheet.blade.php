<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        table,
        td {
            border: solid 2px black;
            border-collapse: collapse;
        }

        td {
            padding: 0.3em;
        }

        .container {
            justify-content: center;
        }



        .horizontal div {
            display: inline-block;
            padding: space-evenly
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="horizontal div" style="width:100%;margin-left:20px">
            <div style="width:43%">
                <h1>Taxi In <br>Thrissur</h1>
                {{-- <img src="{{ asset('assets/media/logos/admin.jpeg') }}" alt=""> --}}
            </div>
            <div style="width:25%;margin-left:5px">
                <h1>TRIP SHEET</h1>
            </div>
            <div style="width:30% ;margin-rigth:50px">
                <p>Ph: 9387 022 022, 7293 568 645 <br> E-mail: taxiinthrissur@gmail.com <br>www.taxiinthrissur.com</p>
            </div>
            <div class="horizontal div" style="width:100%;">
                <div style="width:49%">
                    <h5>TRN NO :</h4>
                </div>
                <div style="width:25%">
                    <h5>Booking ID :</h4>
                </div>
                <div style="width:25%">
                    <h5>DATE :2023-06-05</h4>
                </div>

            </div>

        </div>
        <div style="width: 100%">
            <table style="width: 100%">
                <thead>
                    <tr>
                        <td>AC</td>
                        <td>Driver Name :</td>
                        <td>Vechile Name :</td>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Client Name : SDFDDF</td>
                        <td colspan="2">TRIP ID : EWREFERGET</td>

                    </tr>
                    <tr>
                        <td>
                            <h4>DETAILS OF TRIP </h4>
                            <h5>Pick Up :</h5>
                            <h5>Drop :</h5>
                        </td>

                        <td colspan="2">Visite Place :</td>

                    </tr>
                    <tr>
                        <td>GR St. KoM. : </td>
                        <td>GR CI. K.M. : </td>
                        <td>Total KM:</td>
                    </tr>
                    <tr>
                        <td>GR St. Time: </td>
                        <td>GR. CI. Time: </td>
                        <td>Total Time:</td>
                    </tr>
                    <tr>
                        <td>St. K. M:</td>
                        <td>CI. K. M:</td>
                        <td>Total KM:</td>
                    </tr>
                    <tr>
                        <td>St. Time:</td>
                        <td>CI. Time :</td>
                        <td>Total Time:</td>
                    </tr>
                    <tr>
                        <td>Number of days</td>
                        <td>Number of Nigth halt</td>
                        <td rowspan="2">Customer's Name & Signature :</td>

                    </tr>
                    <tr>
                        <td>Perimit,Toll & Parking</td>
                        <td>Diesal:</td>
                    </tr>
                    <tr>
                        <td>Amount Rs :</td>
                        <td></td>
                        <td rowspan="3">Manager Signature : </td>
                    </tr>
                    <tr>
                        <td>Driver Betta:</td>
                        <td></td>
                        <!-- <td colspan="4" rowspan="2">B </td> -->
                    </tr>
                    <tr>
                        <td>Amount in Words:two thousand thirty-four</td>
                        <td></td>
                        <!-- <td rowspan="2">B </td> -->
                    </tr>
                </tbody>

            </table>
        </div>
    </div>
</body>

</html>
