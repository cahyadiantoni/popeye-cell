        <nav class="navbar header-navbar pcoded-header">
          <div class="navbar-wrapper">
            <div class="navbar-logo">
              <a class="mobile-menu" id="mobile-collapse" href="#!">
                <i class="feather icon-menu"></i>
              </a>
              <a href="index.html">
                <img class="img-fluid" src="{{ asset('files/assets/images/logo.png') }}" alt="Theme-Logo" style="width: 150px; height: auto;" />
              </a>
              <a class="mobile-options">
                <i class="feather icon-more-horizontal"></i>
              </a>
            </div>

            <div class="navbar-container">
              <ul class="nav-left">
                <li class="header-search">
                  <div class="main-search morphsearch-search">
                    <div class="input-group">
                      <span class="input-group-prepend search-close">
                        <i class="feather icon-x input-group-text"></i>
                      </span>
                      <input type="text" class="form-control" placeholder="Enter Keyword" />
                      <span class="input-group-append search-btn">
                        <i class="feather icon-search input-group-text"></i>
                      </span>
                    </div>
                  </div>
                </li>
                <li>
                  <a href="#!" onclick="javascript:toggleFullScreen()" class="waves-effect waves-light">
                    <i class="full-screen feather icon-maximize"></i>
                  </a>
                </li>
              </ul>
              <ul class="nav-right">
                <li class="header-notification">
                  <div class="dropdown-primary dropdown">
                  <div class="dropdown-toggle" data-bs-toggle="dropdown" onclick="window.location='{{ url('/terima-barang') }}'">
                      <i class="feather icon-bell"></i>
                      <span class="badge bg-c-pink">{{ $requestCount }}</span>
                  </div>
                    <!-- <ul class="show-notification notification-view dropdown-menu" data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">
                      <li>
                        <h6>Notifications</h6>
                        <label class="form-label label label-danger">New</label>
                      </li>
                      <li>
                        <div class="d-flex">
                          <div class="flex-shrink-0">
                            <img class="d-flex align-self-center img-radius" src="{{ asset('files/assets/images/avatar-4.jpg')}}" alt="Generic placeholder image" />
                          </div>
                          <div class="flex-grow-1">
                            <h5 class="notification-user">John Doe</h5>
                            <p class="notification-msg">Lorem ipsum dolor sit amet, consectetuer elit.</p>
                            <span class="notification-time">30 minutes ago</span>
                          </div>
                        </div>
                      </li>
                      <li>
                        <div class="d-flex">
                          <div class="flex-shrink-0">
                            <img class="d-flex align-self-center img-radius" src="{{ asset('files/assets/images/avatar-3.jpg')}}" alt="Generic placeholder image" />
                          </div>
                          <div class="flex-grow-1">
                            <h5 class="notification-user">Joseph William</h5>
                            <p class="notification-msg">Lorem ipsum dolor sit amet, consectetuer elit.</p>
                            <span class="notification-time">30 minutes ago</span>
                          </div>
                        </div>
                      </li>
                      <li>
                        <div class="d-flex">
                          <div class="flex-shrink-0">
                            <img class="d-flex align-self-center img-radius" src="{{ asset('files/assets/images/avatar-4.jpg')}}" alt="Generic placeholder image" />
                          </div>
                          <div class="flex-grow-1">
                            <h5 class="notification-user">Sara Soudein</h5>
                            <p class="notification-msg">Lorem ipsum dolor sit amet, consectetuer elit.</p>
                            <span class="notification-time">30 minutes ago</span>
                          </div>
                        </div>
                      </li>
                    </ul> -->
                  </div>
                </li>
                <!-- <li class="header-notification">
                  <div class="dropdown-primary dropdown">
                    <div class="displayChatbox dropdown-toggle" data-bs-toggle="dropdown">
                      <i class="feather icon-message-square"></i>
                      <span class="badge bg-c-green">3</span>
                    </div>
                  </div>
                </li> -->
                <li class="user-profile header-notification">
                  <div class="dropdown-primary dropdown">
                    <div class="dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="{{ asset('files/assets/images/avatar-4.jpg') }}" class="img-radius" alt="User-Profile-Image" />
                        <span>{{ Auth::user()->name }}</span>
                        <i class="feather icon-chevron-down"></i>
                    </div>
                    <ul class="show-notification profile-notification dropdown-menu" data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">
                      <li>
                        <a href="#!"> <i class="feather icon-settings"></i> Settings </a>
                      </li>
                      <li>
                        <a href="user-profile.html"> <i class="feather icon-user"></i> Profile </a>
                      </li>
                      <li>
                        <a href="email-inbox.html"> <i class="feather icon-mail"></i> My Messages </a>
                      </li>
                      <li>
                        <a href="auth-lock-screen.html"> <i class="feather icon-lock"></i> Lock Screen </a>
                      </li>
                      <li>
                      <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                          <i class="feather icon-log-out"></i> Logout
                      </a>

                      <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                          @csrf
                      </form>
                      </li>
                    </ul>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </nav>