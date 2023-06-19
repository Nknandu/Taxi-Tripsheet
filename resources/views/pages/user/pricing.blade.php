@extends('layouts.user.user_app', ['body_class' => ''])
@section('title', 'Pricing')
@section('content')

<section class="about-banner white-bg">
    <div class="containser">
        <div class="row">
            <img class="img-fluid" src="{{ asset('assets/user/images/about-1.jpg') }}" alt="projectX">
        </div>
    </div>
</section>

<!-- Pricing --------------------------------------------------------------------------------------->

<section class="white-bg pt-14 pb-14 pricing-landing">
    <div class="container">
        <div class="row">
            <div class="pricing-contents">
                <h2 class="font1 black">Simple, affordable pricing.</h2>
                <h1 class="font1 mt-5 mb-5">Easily add personalized videos to your HubSpot emails, snippets, sequences</h1>
            </div>
        </div>

        <!-- FEATURES -->

        <div class="row d-flex align-items-start justify-content-between">
            <div class="col-md-3 pricing-feature-box font1">
                <h5>$0<span class="font1">/ Year</span></h5>
                <h3>Basic Free</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
                <ul class="font1">
                    <li>Single Website Activation</li>
                    <li>05 Blocks</li>
                    <li>Google Fonts</li>
                    <li>05 Template Kits</li>
                    <li>05 Site Kits</li>
                    <li>Free Support</li>
                </ul>
                <button type="button" class="btn-primary border-btn mt-4">Free Plan</button>
            </div>

            <div class="col-md-3 pricing-feature-box font1">
                <h5>$99<span class="font1">/ Year</span></h5>
                <h3>Plus</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
                <ul class="font1">
                    <li>05 Website Activation</li>
                    <li>Unlimited Pro Blocks</li>
                    <li>Unlimited Pro Fonts</li>
                    <li>Unlimited Pro Template Kits</li>
                    <li>Unlimited Pro Site Kits</li>
                    <li>Quick Support</li>
                </ul>
                <button type="button" class="btn-primary border-btn mt-4">Buy Now</button>
            </div>

            <div class="col-md-3 pricing-feature-box font1">
                <h5>$199<span class="font1">/ Year</span></h5>
                <h3>Pro</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
                <ul class="font1">
                    <li>25 Website Activation</li>
                    <li>Unlimited Pro Blocks</li>
                    <li>Unlimited Pro Fonts</li>
                    <li>Unlimited Pro Template Kits</li>
                    <li>Unlimited Pro Site Kits</li>
                    <li>Quick Support</li>
                </ul>
                <button type="button" class="btn-primary border-btn mt-4">Buy Now</button>
            </div>
        </div>

        <!-- FEATURES -->

        <div class="row d-flex align-items-start justify-content-center mt-6">
            <div class="col-md-12 ht-price pricing-feature-box font1 d-flex align-items-start justify-content-between">
                <div class="col-md-6">
                    <h5>$299<span class="font1">/ Year</span></h5>
                    <h3>Agency</h3>
                    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes.</p>
                    <button type="button" class="btn-primary border-btn mt-4">Buy Now</button>
                </div>
                <div class="agency-pricing-features col-md-3 d-flex align-items-center justify-content-between">
                    <ul class="font1">
                        <li>Unlimited Website Activation</li>
                        <li>Unlimited Pro Blocks</li>
                        <li>Unlimited Pro Fonts</li>
                        <li>Unlimited Pro Template Kits</li>
                        <li>Unlimited Pro Site Kits</li>
                        <li>Priority Support</li>
                    </ul>
                </div>
                <div class="agency-pricing-features col-md-3 d-flex align-items-center justify-content-between">
                    <ul class="font1">
                        <li>WooCommerce Pro Blocks</li>
                        <li>Unlimited Pro Fonts</li>
                        <li>Unlimited Ecommerce Template Kits</li>
                        <li>Unlimited Ecommerce Pro Site Kits</li>
                        <li>Ready made Woocommerce Websites</li>
                        <li>Pro Shop layouts</li>
                    </ul>
                </div>


                <!-- <div class="ht-img">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div> -->
            </div>
        </div>

        <div class="further-links row">
            <div class="custom-licence d-flex align-items-center justify-content-start font1 mt-6">
                <h3>Looking for Agency-level licensing?</h3>
                <button type="button" class="btn-primary border-btn ml-20p">Contact us</button>
            </div>
        </div>




    </div>
</section>



<!-- INTERMEDIATE --------------------------------------------------------------------------------------->

<section class="intermediate-two pt-10 pb-10">
    <!-- <div class="container">
      <div class="row d-flex align-items-center justify-content-center">
        <div class="col-md-6 INT01-txts">
          <div class="INT01-contents mt-4 mb-4">
            <h1 class="font1">Get a Unique Website Design for your next project.</h1>
          </div>
          <button type="button" class="btn-primary border-btn">Hire Designer</button>
        </div>
        <div class="col-md-6 INT01-img">
          <img class="img-fluid px-logo" src="images/INT1.png" alt="projectX">
        </div>
      </div>
    </div> -->
</section>

<!-- FAQ --------------------------------------------------------------------------------------->


<section class="pt-14 pb-14 FAQ-wrap font1 white-bg">
    <div class="container">
        <div class="row">
            <div class="FAQ-contents">
                <h2 class="font1 black">FAQ</h2>
                <h1 class="font1 mt-5 pb-4">Frequently Asked Questions</h1>
            </div>
        </div>
        <div class="accordion pt-4" id="accordionExample">
            <div class="card">
                <div class="card-header" id="headingOne">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Who are Elementor Cloud Websites for?
                        </button>
                    </h2>
                </div>

                <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample">
                    <div class="card-body">
                        Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet

                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="headingTwo">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Can I build more than one Elementor Cloud Website?
                        </button>
                    </h2>
                </div>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample">
                    <div class="card-body">
                        Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="headingThree">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            What will happen if I surpass the bandwidth/visitors/storage limit?
                        </button>
                    </h2>
                </div>
                <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                    <div class="card-body">
                        Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
