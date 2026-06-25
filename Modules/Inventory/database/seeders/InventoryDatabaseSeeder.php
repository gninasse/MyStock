<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\Article;
use Modules\Inventory\Models\Category;
use Modules\Inventory\Models\Store;

class InventoryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Catégories
        $cats = [
            ['code' => 'INF', 'name' => 'Informatique & Réseau'],
            ['code' => 'BUR', 'name' => 'Bureautique & Papeterie'],
            ['code' => 'MED', 'name' => 'Matériel Médical'],
            ['code' => 'SAN', 'name' => 'Sanitaire & Hygiène'],
        ];

        $categoryMap = [];
        foreach ($cats as $cat) {
            $categoryMap[$cat['code']] = Category::firstOrCreate(['code' => $cat['code']], $cat);
        }

        // 30 Articles
        $articlesData = [
            // Informatique
            ['code' => 'ART-INF-001', 'designation' => 'Ordinateur de Bureau HP ProDesk', 'cat' => 'INF', 'unit' => 'Pièce', 'min_stock' => 5],
            ['code' => 'ART-INF-002', 'designation' => 'Ordinateur Portable Dell Latitude', 'cat' => 'INF', 'unit' => 'Pièce', 'min_stock' => 3],
            ['code' => 'ART-INF-003', 'designation' => 'Imprimante HP LaserJet Pro', 'cat' => 'INF', 'unit' => 'Pièce', 'min_stock' => 2],
            ['code' => 'ART-INF-004', 'designation' => 'Onduleur APC 1500VA', 'cat' => 'INF', 'unit' => 'Pièce', 'min_stock' => 4],
            ['code' => 'ART-INF-005', 'designation' => 'Écran LCD 24 pouces Dell', 'cat' => 'INF', 'unit' => 'Pièce', 'min_stock' => 5],
            ['code' => 'ART-INF-006', 'designation' => 'Clavier Standard USB Logitech', 'cat' => 'INF', 'unit' => 'Pièce', 'min_stock' => 10],
            ['code' => 'ART-INF-007', 'designation' => 'Souris Optique USB Logitech', 'cat' => 'INF', 'unit' => 'Pièce', 'min_stock' => 10],
            ['code' => 'ART-INF-008', 'designation' => 'Disque Dur Externe 1TB WD', 'cat' => 'INF', 'unit' => 'Pièce', 'min_stock' => 5],
            ['code' => 'ART-INF-009', 'designation' => 'Câble Réseau RJ45 Cat6 3m', 'cat' => 'INF', 'unit' => 'Pièce', 'min_stock' => 20],
            ['code' => 'ART-INF-010', 'designation' => 'Switch D-Link 24 Ports', 'cat' => 'INF', 'unit' => 'Pièce', 'min_stock' => 2],

            // Bureautique
            ['code' => 'ART-BUR-001', 'designation' => 'Rame de Papier A4 Double A 80g', 'cat' => 'BUR', 'unit' => 'Carton', 'min_stock' => 15],
            ['code' => 'ART-BUR-002', 'designation' => 'Boîte de Stylos Bic Bleu (50 pcs)', 'cat' => 'BUR', 'unit' => 'Boîte', 'min_stock' => 10],
            ['code' => 'ART-BUR-003', 'designation' => 'Cahier Registre 200 pages', 'cat' => 'BUR', 'unit' => 'Pièce', 'min_stock' => 20],
            ['code' => 'ART-BUR-004', 'designation' => 'Boîte d\'Agrafes 26/6', 'cat' => 'BUR', 'unit' => 'Boîte', 'min_stock' => 15],
            ['code' => 'ART-BUR-005', 'designation' => 'Classeur à Levier Standard', 'cat' => 'BUR', 'unit' => 'Pièce', 'min_stock' => 30],
            ['code' => 'ART-BUR-006', 'designation' => 'Surligneur Stabilo Boss Assortis', 'cat' => 'BUR', 'unit' => 'Pochette', 'min_stock' => 8],

            // Matériel Médical
            ['code' => 'ART-MED-001', 'designation' => 'Thermomètre Infrarouge Médical', 'cat' => 'MED', 'unit' => 'Pièce', 'min_stock' => 5],
            ['code' => 'ART-MED-002', 'designation' => 'Tensiomètre Électronique Bras', 'cat' => 'MED', 'unit' => 'Pièce', 'min_stock' => 4],
            ['code' => 'ART-MED-003', 'designation' => 'Seringues Stériles 5ml (100 pcs)', 'cat' => 'MED', 'unit' => 'Boîte', 'min_stock' => 12],
            ['code' => 'ART-MED-004', 'designation' => 'Boîte d\'Aiguilles Stériles G21', 'cat' => 'MED', 'unit' => 'Boîte', 'min_stock' => 10],
            ['code' => 'ART-MED-005', 'designation' => 'Oxymètre de Pouls de Doigt', 'cat' => 'MED', 'unit' => 'Pièce', 'min_stock' => 8],
            ['code' => 'ART-MED-006', 'designation' => 'Compresse Stérile 10x10 cm (100 pcs)', 'cat' => 'MED', 'unit' => 'Boîte', 'min_stock' => 15],
            ['code' => 'ART-MED-007', 'designation' => 'Bande de Gaze Elastique 4m x 10cm', 'cat' => 'MED', 'unit' => 'Rouleau', 'min_stock' => 50],
            ['code' => 'ART-MED-008', 'designation' => 'Stéthoscope Clinique Littmann', 'cat' => 'MED', 'unit' => 'Pièce', 'min_stock' => 3],

            // Hygiène & Sanitaire
            ['code' => 'ART-SAN-001', 'designation' => 'Gels Hydroalcooliques 500ml', 'cat' => 'SAN', 'unit' => 'Flacon', 'min_stock' => 20],
            ['code' => 'ART-SAN-002', 'designation' => 'Masques Chirurgicaux 3 Plis (50 pcs)', 'cat' => 'SAN', 'unit' => 'Boîte', 'min_stock' => 25],
            ['code' => 'ART-SAN-003', 'designation' => 'Gants d\'Examen en Latex (100 pcs)', 'cat' => 'SAN', 'unit' => 'Boîte', 'min_stock' => 30],
            ['code' => 'ART-SAN-004', 'designation' => 'Savon Liquide Désinfectant 5L', 'cat' => 'SAN', 'unit' => 'Bidon', 'min_stock' => 8],
            ['code' => 'ART-SAN-005', 'designation' => 'Drap d\'Examen Papier Gaufré', 'cat' => 'SAN', 'unit' => 'Rouleau', 'min_stock' => 20],
            ['code' => 'ART-SAN-006', 'designation' => 'Alcool Éthylique Médical 70% 1L', 'cat' => 'SAN', 'unit' => 'Flacon', 'min_stock' => 15],
        ];

        $articles = [];
        foreach ($articlesData as $item) {
            $catModel = $categoryMap[$item['cat']];
            $articles[] = Article::firstOrCreate(['code' => $item['code']], [
                'designation' => $item['designation'],
                'category_id' => $catModel->id,
                'unit' => $item['unit'],
                'min_stock' => $item['min_stock'],
                'is_active' => true,
            ]);
        }

        // Magasin Central
        $store = Store::firstOrCreate(['code' => 'MAG-01'], [
            'name' => 'Magasin Central',
            'location' => 'Bâtiment A',
            'manager_name' => 'Amadou Diallo',
            'is_active' => true,
        ]);

        // Remplir la table store_frequent_items avec 16 articles
        $store->frequentItems()->delete();
        for ($i = 0; $i < 16; $i++) {
            if (isset($articles[$i])) {
                $store->frequentItems()->create([
                    'article_id' => $articles[$i]->id,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
