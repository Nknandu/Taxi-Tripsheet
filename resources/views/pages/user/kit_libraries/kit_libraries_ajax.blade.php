<section class="white-bg pt-6 pb-6 designs-landing">
    <div class="container">
        <div class="row row-cols-1 row-cols-md-3">
            <!-- Grid item starts here -------------------------------------->
            @foreach ($kit_libraries as $kit_library)
            <div class="col mb-4">
                <div class="card">
                    <a href="{{ url('kit-library/'.$kit_library->slug) }}" class="designs-image-wrap"><img src="{{ $kit_library->getThumbImage() }}" class="card-img-top" alt="projectX"></a>
                    <!-- Favourite button starts here -------------------------------------->
                    <div class="fav-container">
                        <div class="plus-minus">
                            <input type="checkbox" name="a" id="a" class="css-checkbox">
                            <label for="a" class="css-label">
                                <span class="fa fa-plus"><img src="{{ asset('assets/user/images/themes/fav/folder-plus.svg') }}" alt="projectX"></span>
                                <span class="fa fa-minus"><img src="{{ asset('assets/user/images/themes/fav/folder-minus.svg') }}" alt="projectX"></span>
                            </label>
                        </div>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <h5 class="font1 card-title">{{ $kit_library->title }}</h5>
                        <a class="d-flex align-items-center justify-content-center" href="{{ url('kit-library/'.$kit_library->slug) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up-right"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg></a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <nav aria-label="Page navigation example">
            {{ $kit_libraries->links('pagination.default') }}
        </nav>
    </div>
</section>
