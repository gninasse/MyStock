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
              @can('cores.dashboard.view')
              <li class="nav-item">
                <a href="{{ route('etats.tableau-de-bord') }}" class="nav-link {{ request()->routeIs('etats.tableau-de-bord') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-house"></i>
                  <p>Tableau de bord</p>
                </a>
              </li>
              @endcan
              @canany(['cores.users.index', 'cores.roles.index', 'cores.permissions.index', 'cores.modules.index', 'cores.activities.index'])
              <li class="nav-item {{ request()->routeIs('cores.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('cores.*') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-gear"></i>
                  <p>
                    Administration
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  {{-- Tableau de bord technique du boilerplate : réservé à l'administration système --}}
                  @can('cores.modules.index')
                  <li class="nav-item">
                    <a href="{{ route('cores.dashboard') }}" class="nav-link {{ request()->routeIs('cores.dashboard') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-speedometer2"></i>
                      <p>Tableau de bord système</p>
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
                      <p>Journal d'audit</p>
                    </a>
                  </li>
                  @endcan
                </ul>
              </li>
              @endcanany
              @canany(['stock.entrees.saisir', 'stock.sorties.saisir', 'stock.documents.index'])
              <li class="nav-item {{ request()->routeIs('stock.saisie.*', 'stock.documents.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('stock.saisie.*', 'stock.documents.*') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-arrow-left-right"></i>
                  <p>
                    Mouvements
                    @if ($nombreAlertesStock ?? 0)
                      <span class="nav-badge badge text-bg-danger me-3" title="Articles sous le seuil minimum">{{ $nombreAlertesStock }}</span>
                    @endif
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
              @can('stock.inventaires.saisir')
              <li class="nav-item {{ request()->routeIs('stock.inventaires.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('stock.inventaires.*') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-clipboard-check"></i>
                  <p>
                    Inventaire
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('stock.inventaires.create') }}" class="nav-link {{ request()->routeIs('stock.inventaires.create') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-pencil-square"></i>
                      <p>Saisir un inventaire</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('stock.inventaires.index') }}" class="nav-link {{ request()->routeIs('stock.inventaires.index', 'stock.inventaires.show') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-list-check"></i>
                      <p>Liste des inventaires</p>
                    </a>
                  </li>
                </ul>
              </li>
              @endcan
              @canany(['etats.stock-magasin', 'etats.stock-categorie', 'etats.journal', 'etats.fiche-stock'])
              <li class="nav-item {{ request()->routeIs('etats.stock-*', 'etats.journal', 'etats.fiche-stock') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ request()->routeIs('etats.stock-*', 'etats.journal', 'etats.fiche-stock') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-bar-chart"></i>
                  <p>
                    États
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  @can('etats.stock-magasin')
                  <li class="nav-item">
                    <a href="{{ route('etats.stock-magasin') }}" class="nav-link {{ request()->routeIs('etats.stock-magasin') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-shop-window"></i>
                      <p>Stock par magasin</p>
                    </a>
                  </li>
                  @endcan
                  @can('etats.stock-categorie')
                  <li class="nav-item">
                    <a href="{{ route('etats.stock-categorie') }}" class="nav-link {{ request()->routeIs('etats.stock-categorie') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-tags"></i>
                      <p>Stock par catégorie</p>
                    </a>
                  </li>
                  @endcan
                  @can('etats.journal')
                  <li class="nav-item">
                    <a href="{{ route('etats.journal') }}" class="nav-link {{ request()->routeIs('etats.journal') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-journal-text"></i>
                      <p>Journal des mouvements</p>
                    </a>
                  </li>
                  @endcan
                  @can('etats.fiche-stock')
                  <li class="nav-item">
                    <a href="{{ route('etats.fiche-stock') }}" class="nav-link {{ request()->routeIs('etats.fiche-stock') ? 'active' : '' }}">
                      <i class="nav-icon bi bi-file-earmark-text"></i>
                      <p>Fiche de stock</p>
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
