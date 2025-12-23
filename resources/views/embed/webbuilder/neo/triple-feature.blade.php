<section class="ekd-element element-container_33 list-type-  layout-proportion-smallcontent color-light-0 display-on-top-0">
    <div class="container">
        <div class="container-3-cols grid">
            @foreach ($occurences as $event)
            <div>
                <section class="ekd-element element-infobox list-type-   color-light-0 element-bgcolor-0 display-on-top-201">
                    <div class="container layout-0">
                        <div class="is-image">
                            <div class="media-image media-type-jpg media-ctype-infobox">
                                <span class="media-container has-mouseover ekd-copy-white">
                                    <a href="https://gaeufelden.communiapp.de/" class="image-link">
                                        <span class="width-50">
                                            <picture>
                                                <source media="(min-width:0px)" srcset="{{ $event->service->getImageCutUrl('bildschirm-16x9') }}">
                                                <img src="{{ $event->service->getImageCutUrl('bildschirm-16x9') }}" alt="{{ $event->service->titleText(false) }}" />
                                            </picture>
                                        </span>
                                    </a>
                                </span>
                            </div>
                            <div class="media-title-wrapper"></div>
                        </div>
                        <div class="container-title">
                            <h3 class="header-color-standard header-default header-icon-pos-center header-align-center">
                                <span>
                                    {{ $event->service->titleText(false) }}
                            </span>
                            </h3>
                        </div>
                        <div class="container-1-col">
                            <p class="text-center">
                                {{ $event->getAdText('homepage') }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>
            @endforeach
        </div>
    </div>
</section>
