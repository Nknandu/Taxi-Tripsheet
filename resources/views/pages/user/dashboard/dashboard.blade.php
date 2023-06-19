@extends('layouts.user.dashboard_app', ['body_class' => 'kit-page'])
@section('title', 'Dashboard')
@section('content')
<section class="filter-bar-wrap d-flex align-items-start justify-content-center">
    <div class="px-container filter-bar d-flex align-items-start justify-content-end">

        <div class="dropdown show">
            <a class="btn px-filter-btn dropdown-toggle" href="#" role="button" id="dropdownMenuLink0" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Websites
            </a>

            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink0">
                <a class="dropdown-item" href="#">All</a>
                <a class="dropdown-item" href="#">Webhance</a>
                <a class="dropdown-item" href="#">Hooblie</a>
            </div>
        </div>

        <div class="dropdown show">
            <a class="btn px-filter-btn dropdown-toggle" href="#" role="button" id="dropdownMenuLink2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Sort by
            </a>

            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink2">
                <a class="dropdown-item" href="#">Date Installed</a>
                <a class="dropdown-item" href="#">Expired</a>
            </div>
        </div>

        <div class="dropdown show">
            <a class="btn px-filter-btn dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Filter
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
            <div class="livesite-name d-flex align-items-center justify-content-between">
                <div class="install-item-headline d-flex align-items-center justify-content-between">
                    <h2>webhance.net</h2>
                    <a><img class="img-fluid" src="{{ asset('assets/user/images/profile/items/link.svg') }}" alt="projectX"></a>
                </div>
                <div class="info-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-help-circle"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
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


        <!-- website one -->
        <div class="website-items">
            <div class="livesite-name d-flex align-items-center justify-content-between">
                <div class="install-item-headline d-flex align-items-center justify-content-between">
                    <h2>hooblie.net</h2>
                    <a><img class="img-fluid" src="{{ asset('assets/user/images/profile/items/link.svg') }}" alt="projectX"></a>
                </div>
                <div class="info-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-help-circle"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
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
