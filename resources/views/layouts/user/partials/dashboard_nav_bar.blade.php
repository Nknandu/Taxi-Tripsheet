<!-- NAVIGATON --------------------------------------------------------------------------------------->
<section class="dashboard-wrap-menu minus-menu d-flex align-items-start justify-content-center">
    <div class="px-container font1 d-flex align-items-center justify-content-center">

        <div class="dashboard-nav d-flex align-items-center justify-content-between">
            <h4 class="font1"><span>Pro</span>Dashboard</h4>
            <ul class="dashboard-nav-items">
                <li><a href="{{ url('user/dashboard') }}" class="dash-active">Installed</a></li>
                <li><a href="{{ url('user/favorites') }}">Favourites</a></li>
                <li><a href="{{ url('user/settings') }}">Settings</a></li>
            </ul>
            <div class="d-flex align-items-center justify-content-between">
                <a class="dashboard-kill-btn" href="{{ route('logout') }}">Exit Dashboard</a>
                <a href="{{ url('user/settings') }}" class="user-dp"><img class="img-fluid" src="{{ asset('assets/user/images/profile/dp.jpg') }}" alt="projectX"></a>
            </div>
        </div>

    </div>
</section>
