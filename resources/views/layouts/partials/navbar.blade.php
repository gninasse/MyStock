<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4" style="border-color: var(--border-standard) !important; box-shadow: var(--shadow-sm);">
    <div class="container-fluid">
        
        <!-- Mobile Sidebar Toggle -->
        <button class="btn btn-link text-dark d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Search Bar (optional - can be uncommented) -->
        <!--
        <form class="d-none d-md-flex ms-3 flex-grow-1" style="max-width: 400px;">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" class="form-control border-start-0" placeholder="Rechercher..." />
            </div>
        </form>
        -->
        
        <div class="ms-auto"></div>
        
        <!-- Notifications -->
        <div class="dropdown me-3">
            <button class="btn btn-link text-dark position-relative" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">
                    3
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" style="width: 300px;">
                <li class="px-3 py-2 border-bottom">
                    <h6 class="mb-0">Notifications</h6>
                </li>
                <li>
                    <a class="dropdown-item py-3" href="#">
                        <div class="d-flex">
                            <i class="fas fa-box text-primary me-3 mt-1"></i>
                            <div>
                                <div class="fw-semibold">Nouvel article</div>
                                <small class="text-muted">Article ART-001 créé</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center small text-primary" href="#">Voir toutes les notifications</a></li>
            </ul>
        </div>
        
        <!-- User Menu -->
        <div class="dropdown">
            <button class="btn btn-link text-dark text-decoration-none d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-center me-2" style="width: 36px; height: 36px;">
                    <span class="fw-bold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                </div>
                <span class="d-none d-md-inline">{{ auth()->user()->name ?? 'Utilisateur' }}</span>
                <i class="fas fa-chevron-down ms-2 small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li class="px-3 py-2 border-bottom">
                    <div class="small text-muted">Connecté en tant que</div>
                    <div class="fw-semibold">{{ auth()->user()->name ?? 'Utilisateur' }}</div>
                </li>
                <li><a class="dropdown-item py-2" href="{{ route('cores.profile') }}"><i class="fas fa-user me-2"></i> Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                        </button>
                    </form>
                </li>
            </ul>
        </div>
        
    </div>
</nav>
