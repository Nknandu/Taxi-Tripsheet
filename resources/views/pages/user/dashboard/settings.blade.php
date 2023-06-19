@extends('layouts.user.dashboard_app', ['body_class' => 'kit-page'])
@section('title', 'Settings')
@section('content')
    <section class="dash-settings-tab-wrap d-flex align-items-center justify-content-center">
        <div class="px-container d-flex align-items-start justify-content-between">

            <div class="col-md-2 dash-sett-tab dash-profile-tab d-flex flex-column align-items-center justify-content-center">
                <div class="user-sett-dp d-flex align-items-center justify-content-center">
                    <div class="user-sett-dp-ov-hidden"><img class="img-fluid" src="{{ asset('assets/user/images/profile/dp.jpg') }}" alt="projectX"></div>
                    <div class="thumb-pro-badge d-flex align-items-center justify-content-center"><h2>Pro</h2></div>
                </div>
                <h4>{{ auth('web')->user()->first_name }} {{ auth('web')->user()->last_name }}</h4>
                <p>{{ auth('web')->user()->email }}</p>
                <h5>Pro Plan Expires on<span>09-09-24</span</h5>
            </div>

            <div class="col-md-3 settings-blocks">
                <h4>Account Details</h4>
                <form>
                    <div class="form-group">
                        <label for="exampleInputEmail1">First Name</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" value="{{ auth('web')->user()->first_name }}">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Last Name</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" value="{{ auth('web')->user()->last_name }}">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Email address</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" value="{{ auth('web')->user()->email }}">
                    </div>
                    <!-- email permissions -->
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="exampleCheck1">
                        <label class="form-check-label" for="exampleCheck1">Offers, Discounts</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="exampleCheck1">
                        <label class="form-check-label" for="exampleCheck1">Updates, announcements</label>
                    </div>
                    <!-- Update permissions -->
                    <button type="submit" class="btn btn-primary black-btn update-form">Update Details</button>
                </form>
            </div>

            <div class="col-md-3 settings-blocks">
                <h4>Change Password</h4>
                <form>
                    <div class="form-group">
                        <label for="exampleInputPassword1">Current Password</label>
                        <input type="password" class="form-control" id="exampleInputPassword1" placeholder="Current Password">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputPassword1">New Password</label>
                        <input type="password" class="form-control" id="exampleInputPassword1" placeholder="New Password">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputPassword1">Verify New Password</label>
                        <input type="password" class="form-control" id="exampleInputPassword1" placeholder="Verify New Password">
                    </div>
                    <button type="submit" class="btn btn-primary black-btn update-form">Change Password</button>
                </form>
            </div>

            <div class="col-md-3 settings-blocks">
                <h4>Payment History</h4>
                <div class="payment-history-text d-flex align-items-center justify-content-between">
                    <h5>09-09-24</h5>
                    <h6>$199</h6>
                </div>
            </div>

        </div>
    </section>
@endsection
