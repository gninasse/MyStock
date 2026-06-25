<div class="lumiere-sidebar bg-dark text-white position-fixed top-0 bottom-0 start-0" style="width: 260px; overflow-y: auto; z-index: 1000;">
    
    <!-- Logo -->
    <div class="p-4 border-bottom border-secondary">
        <h3 class="h5 mb-0">
            <i class="fas fa-boxes me-2 text-primary"></i>
            Lumière Gestion
        </h3>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="py-3">
        
        <!-- Dashboard / Home Link -->
        <a href="{{ route('cores.dashboard') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('cores.dashboard') ? 'bg-primary' : '' }} hover-bg-dark">
            <i class="fas fa-tachometer-alt me-3" style="width: 20px;"></i>
            <span>Tableau de bord</span>
        </a>
        
        <!-- Administration Section (Core) -->
        @if(auth()->user()->can('cores.users.index') || auth()->user()->can('cores.roles.index') || auth()->user()->can('cores.permissions.index') || auth()->user()->can('cores.modules.index') || auth()->user()->can('cores.activities.index'))
            <div class="px-4 py-2 mt-3">
                <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                    Administration
                </small>
            </div>
            
            @can('cores.users.index')
                <a href="{{ route('cores.users.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('cores.users.*') ? 'bg-primary' : '' }} hover-bg-dark">
                    <i class="fas fa-users me-3" style="width: 20px;"></i>
                    <span>Utilisateurs</span>
                </a>
            @endcan
            
            @can('cores.roles.index')
                <a href="{{ route('cores.roles.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('cores.roles.*') ? 'bg-primary' : '' }} hover-bg-dark">
                    <i class="fas fa-user-shield me-3" style="width: 20px;"></i>
                    <span>Rôles</span>
                </a>
            @endcan
            
            @can('cores.permissions.index')
                <a href="{{ route('cores.permissions.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('cores.permissions.*') ? 'bg-primary' : '' }} hover-bg-dark">
                    <i class="fas fa-key me-3" style="width: 20px;"></i>
                    <span>Permissions</span>
                </a>
            @endcan
            
            @can('cores.modules.index')
                <a href="{{ route('cores.modules.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('cores.modules.*') ? 'bg-primary' : '' }} hover-bg-dark">
                    <i class="fas fa-cubes me-3" style="width: 20px;"></i>
                    <span>Modules</span>
                </a>
            @endcan
            
            @can('cores.activities.index')
                <a href="{{ route('cores.activities.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('cores.activities.*') ? 'bg-primary' : '' }} hover-bg-dark">
                    <i class="fas fa-history me-3" style="width: 20px;"></i>
                    <span>Journal d'activités</span>
                </a>
            @endcan
        @endif
        
        <!-- Inventory Section -->
        <div class="px-4 py-2 mt-3">
            <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                Stock
            </small>
        </div>
        
        <a href="{{ route('inventory.entries.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('inventory.entries.*') ? 'bg-primary' : '' }} hover-bg-dark">
            <i class="fas fa-exchange-alt me-3" style="width: 20px;"></i>
            <span>Mouvements de stock</span>
        </a>
        
        <!-- Referentials Section -->
        <div class="px-4 py-2 mt-3">
            <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                Référentiels
            </small>
        </div>
        
        <a href="{{ route('inventory.stores.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('inventory.stores.*') ? 'bg-primary' : '' }} hover-bg-dark">
            <i class="fas fa-warehouse me-3" style="width: 20px;"></i>
            <span>Magasins</span>
        </a>
        
        <a href="{{ route('inventory.articles.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('inventory.articles.*') ? 'bg-primary' : '' }} hover-bg-dark">
            <i class="fas fa-box me-3" style="width: 20px;"></i>
            <span>Articles</span>
        </a>
        
        <a href="{{ route('inventory.categories.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('inventory.categories.*') ? 'bg-primary' : '' }} hover-bg-dark">
            <i class="fas fa-tags me-3" style="width: 20px;"></i>
            <span>Catégories</span>
        </a>
        
        @if(Route::has('organization.index'))
            <a href="{{ route('organization.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('organization.*') ? 'bg-primary' : '' }} hover-bg-dark">
                <i class="fas fa-sitemap me-3" style="width: 20px;"></i>
                <span>Organisation</span>
            </a>
        @endif
        
        <!-- Reports Section -->
        @if(Route::has('reporting.index'))
            <div class="px-4 py-2 mt-3">
                <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                    Rapports
                </small>
            </div>
            
            <a href="{{ route('reporting.index') }}" class="d-flex align-items-center px-4 py-3 text-white text-decoration-none {{ request()->routeIs('reporting.*') ? 'bg-primary' : '' }} hover-bg-dark">
                <i class="fas fa-chart-bar me-3" style="width: 20px;"></i>
                <span>États & Statistiques</span>
            </a>
        @endif
        
    </nav>
    
</div>

<style>
.lumiere-sidebar .hover-bg-dark:hover {
    background-color: rgba(255, 255, 255, 0.1);
}
</style>
