<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css"
        integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css?family=Roboto:400,500,700,900&display=swap');

        body {
            /* padding: 100px 0;
            background: #ecf0f4;
            width: 100%;
            height: 100%;

            line-height: 1.5; */
            background: #ecf0f4;
            font-size: 18px;
            font-family: 'Roboto', sans-serif;
            color: #222;
        }

        .container {
            max-width: 1230px;
            width: 100%;
        }

        .header {
            margin-bottom: 80px;
        }

        h1 {
            font-weight: 700;
            font-size: 45px;
            font-family: 'Roboto', sans-serif;
        }

        #description {
            font-size: 24px;
        }

        #signature-pad {
            border: 1px solid black;
        }

        .form-wrap {
            background: rgba(255, 255, 255, 1);
            width: 100%;
            max-width: 850px;
            padding: 50px;
            margin: 0 auto;
            position: relative;
            -webkit-border-radius: 10px;
            -moz-border-radius: 10px;
            border-radius: 10px;
            -webkit-box-shadow: 0px 0px 40px rgba(0, 0, 0, 0.15);
            -moz-box-shadow: 0px 0px 40px rgba(0, 0, 0, 0.15);
            box-shadow: 0px 0px 40px rgba(0, 0, 0, 0.15);
        }

        .form-wrap:before {
            content: "";
            width: 90%;
            height: calc(100% + 60px);
            left: 0;
            right: 0;
            margin: 0 auto;
            position: absolute;
            top: -30px;
            background: #00bcd9;
            z-index: -1;
            opacity: 0.8;
            -webkit-border-radius: 10px;
            -moz-border-radius: 10px;
            border-radius: 10px;
            -webkit-box-shadow: 0px 0px 40px rgba(0, 0, 0, 0.15);
            -moz-box-shadow: 0px 0px 40px rgba(0, 0, 0, 0.15);
            box-shadow: 0px 0px 40px rgba(0, 0, 0, 0.15);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group>label {
            display: block;
            font-size: 18px;
            color: #000;
        }

        .form-control {
            height: 50px;
            background: #ecf0f4;
            border-color: transparent;
            padding: 0 15px;
            font-size: 16px;
            -webkit-transition: all 0.3s ease-in-out;
            -moz-transition: all 0.3s ease-in-out;
            -o-transition: all 0.3s ease-in-out;
            transition: all 0.3s ease-in-out;
        }

        .form-control:focus {
            border-color: #00bcd9;
            -webkit-box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
            -moz-box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
            box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
        }

        textarea.form-control {
            height: 160px;
            padding-top: 15px;
            resize: none;
        }

        .btn {
            padding: .657rem .75rem;
            font-size: 18px;
            letter-spacing: 0.050em;
            -webkit-transition: all 0.3s ease-in-out;
            -moz-transition: all 0.3s ease-in-out;
            -o-transition: all 0.3s ease-in-out;
            transition: all 0.3s ease-in-out;
        }

        .btn-primary {
            color: #fff;
            background-color: #00bcd9;
            border-color: #00bcd9;
        }

        .btn-primary:hover {
            color: #00bcd9;
            background-color: #ffffff;
            border-color: #00bcd9;
            -webkit-box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
            -moz-box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
            box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
        }

        .btn-primary:focus,
        .btn-primary.focus {
            color: #00bcd9;
            background-color: #ffffff;
            border-color: #00bcd9;
            -webkit-box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
            -moz-box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
            box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
        }

        .btn-primary:not(:disabled):not(.disabled):active,
        .btn-primary:not(:disabled):not(.disabled).active,
        .show>.btn-primary.dropdown-toggle {
            color: #00bcd9;
            background-color: #ffffff;
            border-color: #00bcd9;
        }

        .btn-primary:not(:disabled):not(.disabled):active:focus,
        .btn-primary:not(:disabled):not(.disabled).active:focus,
        .show>.btn-primary.dropdown-toggle:focus {
            -webkit-box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
            -moz-box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
            box-shadow: 0px 0px 20px rgba(0, 0, 0, .1);
        }
    </style>
</head>

<body>
    {{-- <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

        </div>
    </nav> --}}
    <div class="container">
        {{-- <img class="mx-auto d-block" src="http://www.taxiinthrissur.com/wp-content/uploads/2016/11/taxi-in-thrissur-logo-2-1.png" alt=""> --}}
        <header class="header">

            <h1 id="title" class="text-center">Taxi In Thrissur</h1>
            <p id="description" class="text-center">Trip Sheet</p>
        </header>



        <div class="form-wrap">
            <form class="needs-validation" novalidate>
                <div class="form-row">
                    <div class="col-md-4 mb-3">
                        <label for="validationCustomUsername">Vehicle Trip</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="inlineCheckbox1" value="option1">
                            <label class="form-check-label" for="inlineCheckbox1">A/C</label>
                          </div>
                          <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="inlineCheckbox2" value="option2">
                            <label class="form-check-label" for="inlineCheckbox2">NON A/C</label>
                          </div>

                        {{-- <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-person-circle"></i></span>
                            </div>
                            {{-- <input type="text" class="form-control " id="validationCustomUsername"
                                placeholder="Driver Name" aria-describedby="inputGroupPrepend" required> -}}
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div> --}}
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="validationCustomUsername">Driver Name</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-person-circle"></i></span>
                            </div>
                            <input type="text" class="form-control " id="validationCustomUsername"
                                placeholder="Driver Name" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="validationCustomUsername">Vehicle Number</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-123"></i></span>
                            </div>
                            <input type="text" class="form-control" id="validationCustomUsername"
                                placeholder="Number plate" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    {{-- <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Booking Id</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-file-earmark-text-fill"></i></span>
                            </div>
                            <input type="text" class="form-control" id="validationCustomUsername"
                                placeholder="Booking Id" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div> --}}
                </div>
                <div class="form-row">
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Client Name</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-people-fill"></i></span>
                            </div>
                            <input type="text" class="form-control" id="validationCustomUsername"
                                placeholder="Guest Name" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Address</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-geo-alt"></i></span>
                            </div>
                            <input type="text" class="form-control" id="validationCustomUsername"
                                placeholder="Address" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-md-3 mb-3">
                        <label for="validationCustomUsername">Pick Up</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-geo-alt-fill"></i></span>
                            </div>
                            <input type="text" class="form-control" id="validationCustomUsername"
                                placeholder="Enter Pick Up" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="validationCustomUsername">Drop</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-geo-alt-fill"></i></span>
                            </div>
                            <input type="text" class="form-control" id="validationCustomUsername"
                                placeholder="Enter Drop " aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Visit Place</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-geo-alt-fill"></i></span>
                            </div>
                            <input type="text" class="form-control" id="validationCustomUsername"
                                placeholder="Enter Place " aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                </div>
                {{-- <div class="form-row">
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Enter Trip Date</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-calendar-event"></i></span>
                            </div>
                            <input type="date" class="form-control" id="validationCustomUsername"
                                placeholder="Enter Trip Date" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">End Trip Date</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-calendar-event"></i></span>
                            </div>
                            <input type="date" class="form-control" id="validationCustomUsername"
                                placeholder="Enter Trip Date" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                </div> --}}

                {{-- <div class="form-row">
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Enter Trip Date</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-car-front-fill"></i></span>
                            </div>
                            <select name="" class="form-control" id="">
                                <option selected="" value="" disabled="" class="F8vzy2 HDqSrI">
                                    Choose Your Vehicle</option>
                                <option value="Hatchback" class="F8vzy2" aria-selected="true">Hatchback
                                </option>
                                <option value="Sedan" class="F8vzy2" aria-selected="false">Sedan</option>
                                <option value="SUV" class="F8vzy2" aria-selected="false">SUV</option>
                                <option value="Innova" class="F8vzy2" aria-selected="false">Innova</option>
                                <option value="Innova crista" class="F8vzy2" aria-selected="false">Innova
                                    crista
                                </option>
                                <option value="Item 1" class="F8vzy2" aria-selected="false">Tumbo Traveller
                                </option>
                                <option value="Item 2" class="F8vzy2" aria-selected="false">Bus</option>
                            </select>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Number plate</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-123"></i></span>
                            </div>
                            <input type="text" class="form-control" id="validationCustomUsername"
                                placeholder="Number plate" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                </div> --}}
                {{-- <div class="form-row">
                    <div class="col-md-12 mb-3">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-file-earmark-arrow-up-fill"></i></span>
                            </div>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="customFile">
                                <label class="custom-file-label" for="customFile">Choose file</label>
                            </div>
                        </div>
                    </div>
                </div> --}}
                <div class="form-row">

                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Starting KM Garage</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-geo-alt-fill"></i></span>
                            </div>
                            <input type="number" class="form-control" id="validationCustomUsername"
                                placeholder="Starting KM Garage" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">End KM Garage</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-geo-alt-fill"></i></span>
                            </div>
                            <input type="number" class="form-control" id="validationCustomUsername"
                                placeholder="End KM Garage" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    {{-- <div class="col-md-4 mb-3">
                        <label for="validationCustomUsername">Total KM </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-geo-alt-fill"></i></span>
                            </div>
                            <input type="number" class="form-control" id="validationCustomUsername"
                                placeholder="End KM Garage" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div> --}}

                </div>
                <div class="form-row">

                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Starting time Garage</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-alarm"></i></span>
                            </div>
                            <input type="time" class="form-control" id="validationCustomUsername"
                                placeholder="Starting time Garage" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">End Time Garage</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-alarm"></i></span>
                            </div>
                            <input type="time" class="form-control" id="validationCustomUsername"
                                placeholder="End Time Garage" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    {{-- <div class="col-md-4 mb-3">
                        <label for="validationCustomUsername">Total Time </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-alarm"></i></span>
                            </div>
                            <input type="time" class="form-control" id="validationCustomUsername"
                                placeholder="End Time Garage" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div> --}}


                </div>
                <div class="form-row">

                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Starting KM Pickup Point</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-browser-safari"></i></span>
                            </div>
                            <input type="number" class="form-control" id="validationCustomUsername"
                                placeholder="Enter Trip Date" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">End KM Drop Point</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-browser-safari"></i></span>
                            </div>
                            <input type="number" class="form-control" id="validationCustomUsername"
                                placeholder="End KM Drop Point" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    {{-- <div class="col-md-4 mb-3">
                        <label for="validationCustomUsername">Total KM </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-browser-safari"></i></span>
                            </div>
                            <input type="number" class="form-control" id="validationCustomUsername"
                                placeholder="End KM Drop Point" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div> --}}

                </div>

                <div class="form-row">

                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Starting time Pickup Point</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-alarm"></i></span>
                            </div>
                            <input type="time" class="form-control" id="validationCustomUsername"
                                placeholder="Starting time Pickup Point" aria-describedby="inputGroupPrepend"
                                required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">End Time Drop Point</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-alarm"></i></span>
                            </div>
                            <input type="time" class="form-control" id="validationCustomUsername"
                                placeholder="End Time Drop Point" aria-describedby="inputGroupPrepend" required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div>
                    {{-- <div class="col-md-4 mb-3">
                        <label for="validationCustomUsername">Total time </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroupPrepend"><i
                                        class="bi bi-alarm"></i></span>
                            </div>
                            <input type="time" class="form-control" id="validationCustomUsername"
                                placeholder="Starting time Pickup Point" aria-describedby="inputGroupPrepend"
                                required>
                            <div class="invalid-feedback">
                                Please choose a username.
                            </div>
                        </div>
                    </div> --}}

                </div>

                <div class="form-row">

                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Number of Days </label>
                        <input type="number" class="form-control" id="validationCustomUsername"
                            placeholder="Number of Days" aria-describedby="inputGroupPrepend" required>
                        <div class="invalid-feedback">
                            Please choose a username.
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername"> Number of Nigth halt </label>
                        {{-- <div class="input-group"> --}}

                        <input type="number" class="form-control" id="validationCustomUsername"
                            placeholder="Total Days" aria-describedby="inputGroupPrepend" required>
                        <div class="invalid-feedback">
                            Please choose a username.
                        </div>
                        {{-- </div> --}}
                    </div>

                </div>
                <div class="form-row">

                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername">Perimit,Toll & Parking </label>
                        <input type="number" class="form-control" id="validationCustomUsername"
                            placeholder="Number of Days" aria-describedby="inputGroupPrepend" required>
                        <div class="invalid-feedback">
                            Please choose a username.
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="validationCustomUsername"> Diesel </label>
                        {{-- <div class="input-group"> --}}

                        <input type="number" class="form-control" id="validationCustomUsername"
                            placeholder="Total Days" aria-describedby="inputGroupPrepend" required>
                        <div class="invalid-feedback">
                            Please choose a username.
                        </div>
                        {{-- </div> --}}
                    </div>

                </div>
                <div class="form-group" style="width: 100%">
                    <label for="signature">Signature:</label><br>
                    <canvas id="signature-pad" ></canvas><br>

                </div>
                {{-- <div class="form-row">

                    <div class="col-md-12 form-group">
                        <label for="signature">Remark:</label><br>
                        <textarea class="form-control z-depth-1" id="exampleFormControlTextarea6" rows="3"
                            placeholder="Write something here..."></textarea>

                    </div>
                </div> --}}

                <button class="btn btn-primary btn-block" type="submit">Submit form</button>
            </form>
        </div>

    </div>







    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js"
        integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js"
        integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous">
    </script>
    <script src="https://unpkg.com/signature_pad"></script>
    <script>
        var canvas = document.getElementById('signature-pad');
        var signaturePad = new SignaturePad(canvas);

        // Save the signature data to the hidden input field
        var signatureInput = document.getElementById('signature-input');
        signatureInput.value = signaturePad.toDataURL();
    </script>

    <script>
        // Add the following code if you want the name of the file appear on select
        $(".custom-file-input").on("change", function() {
            var fileName = $(this).val().split("\\").pop();
            $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        });
    </script>


</body>

</html>
