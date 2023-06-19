@section('title', 'Sign In')
<!DOCTYPE html>
<html lang="en">
@include('layouts.user.partials.head')
<body  class="body-bg login-page">
<!-- PAGE ELEMENTS STARTS HERE -->
<!-- NAVIGATON --------------------------------------------------------------------------------------->
<section class="px-primary-nav white-bg d-flex align-items-center">
    <div class="container-fluid all-zero">
        <div class="row">
            <div class="col-md-1 d-flex align-items-center justify-content-end">
                <a href="{{ url('') }}"><img class="img-fluid px-logo" src="{{ asset('assets/user/images/logo-d.png') }}" alt="projectX"></a>
            </div>
            <div class="page-menu-ul d-flex justify-content-start align-items-center col-md-4">

            </div>
            <div class="prime-menu-ul d-flex justify-content-end align-items-center col-md-7">
                <!-- <ul>
                   <li><a href="login.html">Login</a></li>
                </ul> -->
                <a href="{{ url('sign-up') }}" class="btn btn-primary border-btn">CREATE ACCOUNT</a>
            </div>
        </div>
    </div>
</section>

<!-- LOGIN FORM --------------------------------------------------------------------------------------->

<section class="login-wrap d-flex justify-content-center align-items-center">

    <div class="login-form-cover d-flex justify-content-center align-items-center">
        <div class="login-form-block white-bg font1">
            <div class="row login-text font1">
                <h2>Sign in to ProjectX</h2>
            </div>
            <form id="user_login_form" method="post">
                <div class="row px-label"><label for="uname">Email</label></div>
                <div class="row px-input"><input type="text" placeholder="name@example.com" name="email" id="email"></div>
                <div class="row px-label"><label for="psw">Password</label></div>
                <div class="row px-input"><input type="password" placeholder="Password" name="password" id="password"></div>
                <div class="row px-tiny-text font1 black"><a href="{{ url('forgot-password') }}">Forgot your password?</a></div>
                <div class="row px-login-btns d-flex justify-content-between align-items-center">
                    <button id="login_button" class="btn btn-primary black-btn">Login</button>
                    <!-- <p class="px-captcha-text text-right">Secure Login with reCAPTCHA subject to Google</br> Terms & Privacy</p> -->
                    <!-- google sign -->
                    <a class="google-sign" href="#"><span>
                      <img class="img-fluid px-logo" src="{{ asset('assets/user/images/google.svg') }}" alt="projectX"></span>Continue with Google
                    </a>
                    <!-- google sign -->
                </div>
            </form>
        </div>
    </div>
</section>





<!-- Bootstrap-addons -->
<script
    src="https://code.jquery.com/jquery-3.7.0.min.js"
    integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g="
    crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
<script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js"></script>
<script src="{{ asset('assets/user/javascripts/focal.js') }}"></script>
<script src="{{ asset('assets/js/user/user.js') }}"></script>
<script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#login_button").click(function(e){
        e.preventDefault();
        loadPreloader(true)
        var login_button = document.querySelector("#login_button");
        var user_login_form = $("#user_login_form");
        user_login_form.validate({
            // lang:'ar',
            rules: {

                email: {
                    required: true,
                    email: true
                },
                password: {
                    required: true,
                },
            },
            // messages : {
            //     email: {
            //         required: "Please Enter Email",
            //         email: "Please Enter Valid Email"
            //     },
            //     password: {
            //         required: "Please Enter Password",
            //     },
            // }
        });
        if (!user_login_form.valid())
        {
            return;
        }
        var email = $("#email").val();
        var password = $("#password").val();
        $.ajax({
            type:'POST',
            url:"{{ route('login') }}",
            data:{email:email, password:password},
            success:function(data){
                if(data.success == true)
                {
                    printSingleSuccessToast(data.message);
                    window.location.href = data.url
                }
                else if(data.success == false)
                {
                    if($.isEmptyObject(data.error))
                    {
                        printSingleErrorToast(data.message);
                    }
                    else
                    {
                        printMultipleErrorToast(data.errors);
                    }
                }
            },
            error: function(xhr, status, error){
                printSingleErrorToast(error);
            },
        });
        loadPreloader(true)
    });
</script>
<!-- ○══════════════════════════════════○ -->
</body>
</html>
