      <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
          <!--begin::Brand Link-->
          <a href="{{ url('/') }}" class="brand-link">
            <!--begin::Brand Image-->
            <img
              src="{{ asset('adminlte/assets/img/AdminLTELogo.png') }}"
              alt="AdminLTE Logo"
              class="brand-image opacity-75 shadow"
            />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-light">CHU-YO</span>
            <!--end::Brand Text-->
          </a>
          <!--end::Brand Link-->
        </div>
        <!--end::Sidebar Brand-->
        <!--begin::Sidebar Wrapper-->
        <div class="sidebar-wrapper">
          <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul
              class="nav sidebar-menu flex-column"
              data-lte-toggle="treeview"
              role="navigation"
              aria-label="Main navigation"
              data-accordion="false"
              id="navigation"
            >
              <li class="nav-item menu-open">
                <a href="#" class="nav-link active">
                  <i class="nav-icon bi bi-speedometer"></i>
                  <p>
                    MYSTOCK
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  @can('cores.dashboard.view')
                  <li class="nav-item">
                    <a href="{{ route('cores.dashboard') }}" class="nav-link {{ request()->routeIs('cores.dashboard') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-speedometer2"></i>
                      <p>Dashboard</p>
                    </a>
                  </li>
                  @endcan
                  @can('cores.users.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.users.index') }}" class="nav-link {{ request()->routeIs('cores.users.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-people"></i>
                      <p>Utilisateurs</p>
                    </a>
                  </li>
                  @endcan
                  @can('cores.roles.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.roles.index') }}" class="nav-link {{ request()->routeIs('cores.roles.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-shield-lock"></i>
                      <p>Rôles</p>
                    </a>
                  </li>
                  @endcan
                  @can('cores.permissions.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.permissions.index') }}" class="nav-link {{ request()->routeIs('cores.permissions.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-key"></i>
                      <p>Permissions</p>
                    </a>
                  </li>
                  @endcan
                  @can('cores.modules.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.modules.index') }}" class="nav-link {{ request()->routeIs('cores.modules.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-box-seam"></i>
                      <p>Modules</p>
                    </a>
                  </li> 
                  @endcan
                  @can('cores.activities.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.activities.index') }}" class="nav-link {{ request()->routeIs('cores.activities.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-clock-history"></i>
                      <p>Activités</p>
                    </a>
                  </li>
                  @endcan
                </ul>
              </li>
              @canany(['stock.entrees.saisir', 'stock.sorties.saisir', 'stock.documents.index'])
              <li class="nav-item {{ request()->routeIs('stock.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-arrow-left-right"></i>
                  <p>
                    Mouvements
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  @can('stock.entrees.saisir')
                  <li class="nav-item">
                    <a href="{{ route('stock.saisie.create', 'entrees') }}" class="nav-link {{ request()->is('stock/entrees/*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-box-arrow-in-down"></i>
                      <p>Nouvelle entrée</p>
                    </a>
                  </li>
                  @endcan
                  @can('stock.sorties.saisir')
                  <li class="nav-item">
                    <a href="{{ route('stock.saisie.create', 'sorties') }}" class="nav-link {{ request()->is('stock/sorties/*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-box-arrow-up"></i>
                      <p>Nouvelle sortie</p>
                    </a>
                  </li>
                  @endcan
                  @can('stock.documents.index')
                  <li class="nav-item">
                    <a href="{{ route('stock.documents.index') }}" class="nav-link {{ request()->routeIs('stock.documents.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-journal-text"></i>
                      <p>Liste des documents</p>
                    </a>
                  </li>
                  @endcan
                </ul>
              </li>
              @endcanany
              @canany(['referentiel.magasins.index', 'referentiel.structures.index', 'referentiel.categories.index', 'referentiel.articles.index', 'referentiel.articles-frequents.manage'])
              <li class="nav-item {{ request()->routeIs('referentiel.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('referentiel.*') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-boxes"></i>
                  <p>
                    Référentiels
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  @can('referentiel.magasins.index')
                  <li class="nav-item">
                    <a href="{{ route('referentiel.magasins.index') }}" class="nav-link {{ request()->routeIs('referentiel.magasins.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-shop"></i>
                      <p>Magasins</p>
                    </a>
                  </li>
                  @endcan
                  @can('referentiel.structures.index')
                  <li class="nav-item">
                    <a href="{{ route('referentiel.structures.index') }}" class="nav-link {{ request()->routeIs('referentiel.structures.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-diagram-3"></i>
                      <p>Structures</p>
                    </a>
                  </li>
                  @endcan
                  @can('referentiel.categories.index')
                  <li class="nav-item">
                    <a href="{{ route('referentiel.categories.index') }}" class="nav-link {{ request()->routeIs('referentiel.categories.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-tags"></i>
                      <p>Catégories</p>
                    </a>
                  </li>
                  @endcan
                  @can('referentiel.articles.index')
                  <li class="nav-item">
                    <a href="{{ route('referentiel.articles.index') }}" class="nav-link {{ request()->routeIs('referentiel.articles.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-box"></i>
                      <p>Articles</p>
                    </a>
                  </li>
                  @endcan
                  @can('referentiel.articles-frequents.manage')
                  <li class="nav-item">
                    <a href="{{ route('referentiel.articles-frequents.index') }}" class="nav-link {{ request()->routeIs('referentiel.articles-frequents.*') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-star"></i>
                      <p>Articles fréquents</p>
                    </a>
                  </li>
                  @endcan
                </ul>
              </li>
              @endcanany
            </ul>
            <!--end::Sidebar Menu-->
          </nav>
        </div>
        <!--end::Sidebar Wrapper-->
      </aside>
      <!--end::Sidebar-->
