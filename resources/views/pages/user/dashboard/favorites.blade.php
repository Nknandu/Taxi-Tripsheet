@extends('layouts.user.dashboard_app', ['body_class' => 'kit-page'])
@section('title', 'Favorites')
@section('content')
    <section class="filter-bar-wrap d-flex align-items-start justify-content-center">
        <div class="px-container filter-bar d-flex align-items-start justify-content-between">

            <div class="dropdown show">
                <a class="btn px-filter-btn dropdown-toggle" href="#" role="button" id="dropdownMenuLink2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Sort by
                </a>

                <div class="dropdown-menu" aria-labelledby="dropdownMenuLink2">
                    <a class="dropdown-item" href="#">Date Whishlisted</a>
                    <a class="dropdown-item" href="#">Created Date</a>
                </div>
            </div>

            <div class="dropdown show">
                <a class="btn px-filter-btn dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Filter Designs
                </a>

                <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                    <a class="dropdown-item" href="#">All</a>
                    <a class="dropdown-item" href="#">Menu</a>
                    <a class="dropdown-item" href="#">Blocks</a>
                    <a class="dropdown-item" href="#">Pages</a>
                    <a class="dropdown-item" href="#">Intermediate</a>
                    <a class="dropdown-item" href="#">Footer</a>
                    <a class="dropdown-item" href="#">Landing</a>
                </div>
            </div>
        </div>
    </section>

    <!-- INTERMEDIATE --------------------------------------------------------------------------------------->


    <section class="dashboard-tab-installed d-flex align-items-start justify-content-center">
        <div class="px-container dash-product-grid-item-wrap">

            <!-- website one -->
            <div class="website-items">


                <div class="row mt-20p">
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/1.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/2.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/3.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/4.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/5.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/6.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                </div>

                <div class="row mt-20p">
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/1.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/2.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/3.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/4.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/5.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/6.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                </div>

                <div class="row mt-20p">
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/1.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/2.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/3.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/4.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/5.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                    <div class="col-md-2">
                        <img class="img-fluid" src="{{ asset('assets/user/images/profile/items/6.jpg') }}" alt="projectX">
                        <h3 class="font1">Plazza Header</h3>
                    </div>
                </div>

            </div>




        </div>
    </section>


    <!-- Pagination -->

    <section class="dash-pagination-wrap d-flex align-items-start justify-content-center">
        <div class="px-container">
            <nav class="" aria-label="...">
                <ul class="font1 pagination pagination-sm justify-content-end">
                    <li class="page-item active" aria-current="page">
                        <span class="page-link">1</span>
                    </li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                </ul>
            </nav>
        </div>
    </section>


@endsection
