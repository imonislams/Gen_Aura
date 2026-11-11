<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HeroImage;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class StoreDemoSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'T-Shirt', 'slug' => 't-shirt', 'description' => 'Everyday cotton tees', 'image' => 'products/ts-white.jpg', 'sort_order' => 0],
            ['name' => 'Shirt', 'slug' => 'shirt', 'description' => 'Formal and casual shirts', 'image' => 'products/shirt-blue.jpg', 'sort_order' => 1],
            ['name' => 'Pant', 'slug' => 'pant', 'description' => 'Chinos and denim', 'image' => 'products/pant-chino.jpg', 'sort_order' => 2],
            ['name' => 'Panjabi', 'slug' => 'panjabi', 'description' => 'Festive ethnic wear', 'image' => 'products/panjabi.jpg', 'sort_order' => 3],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['status' => 'active']
            );
        }

        $categoryIds = Category::pluck('id', 'name');

        $products = [
            [
                'name' => 'Classic White Crew Tee', 'slug' => 'classic-white-crew-tee', 'sku' => 'GA-TS-001',
                'cat' => 'T-Shirt', 'sub' => 'Everyday Comfort', 'sizes' => 'M,L,XL', 'size_stock' => ['M' => 12, 'L' => 15, 'XL' => 8],
                'feat' => '100% Cotton,Breathable,Soft feel,Regular fit',
                'description' => 'Soft, breathable cotton tee in clean white for everyday wear.',
                'price' => 650, 'old_price' => 850, 'featured' => true, 'image' => 'products/ts-white.jpg',
            ],
            [
                'name' => 'Essential Black Tee', 'slug' => 'essential-black-tee', 'sku' => 'GA-TS-002',
                'cat' => 'T-Shirt', 'sub' => 'Everyday Comfort', 'sizes' => 'M,L,XL,XXL', 'size_stock' => ['M' => 10, 'L' => 14, 'XL' => 9, 'XXL' => 4],
                'feat' => '100% Cotton,Breathable,Soft feel,Regular fit',
                'description' => 'A wardrobe staple in deep black, cut for an easy regular fit.',
                'price' => 690, 'old_price' => 900, 'featured' => true, 'image' => 'products/ts-black.jpg',
            ],
            [
                'name' => 'Graphic Print Tee', 'slug' => 'graphic-print-tee', 'sku' => 'GA-TS-003',
                'cat' => 'T-Shirt', 'sub' => 'Street Style', 'sizes' => 'M,L,XL', 'size_stock' => ['M' => 8, 'L' => 11, 'XL' => 6],
                'feat' => 'Soft cotton,Modern print,Regular fit,Easy wash',
                'description' => 'Casual tee with a modern graphic print for relaxed days.',
                'price' => 750, 'old_price' => 950, 'featured' => false, 'image' => 'products/ts-graphic.jpg',
            ],
            [
                'name' => 'Oxford Formal Shirt', 'slug' => 'oxford-formal-shirt', 'sku' => 'GA-SH-001',
                'cat' => 'Shirt', 'sub' => 'Office Ready', 'sizes' => 'M,L,XL,XXL', 'size_stock' => ['M' => 9, 'L' => 13, 'XL' => 7, 'XXL' => 3],
                'feat' => 'Premium fabric,Wrinkle resistant,Slim fit,Easy care',
                'description' => 'Crisp, comfortable Oxford shirt for office and occasions.',
                'price' => 1250, 'old_price' => 1550, 'featured' => true, 'image' => 'products/shirt-blue.jpg',
            ],
            [
                'name' => 'Premium White Shirt', 'slug' => 'premium-white-shirt', 'sku' => 'GA-SH-002',
                'cat' => 'Shirt', 'sub' => 'Timeless Classic', 'sizes' => 'M,L,XL', 'size_stock' => ['M' => 7, 'L' => 10, 'XL' => 5],
                'feat' => 'Premium fabric,Wrinkle resistant,Regular fit,Easy care',
                'description' => 'A timeless white shirt that pairs with everything.',
                'price' => 1350, 'old_price' => 1700, 'featured' => false, 'image' => 'products/shirt-white.jpg',
            ],
            [
                'name' => 'Slim Fit Chino Pant', 'slug' => 'slim-fit-chino-pant', 'sku' => 'GA-PT-001',
                'cat' => 'Pant', 'sub' => 'Stretch Fit', 'sizes' => '30,32,34,36', 'size_stock' => ['30' => 6, '32' => 12, '34' => 9, '36' => 5],
                'feat' => 'Stretch fabric,All-day comfort,Slim fit,Easy care',
                'description' => 'Stretch-fit chino with a clean, modern look.',
                'price' => 1450, 'old_price' => 1800, 'featured' => true, 'image' => 'products/pant-chino.jpg',
            ],
            [
                'name' => 'Classic Denim Jeans', 'slug' => 'classic-denim-jeans', 'sku' => 'GA-PT-002',
                'cat' => 'Pant', 'sub' => 'Everyday Denim', 'sizes' => '30,32,34,36', 'size_stock' => ['30' => 5, '32' => 10, '34' => 8, '36' => 4],
                'feat' => 'Durable denim,Comfort stretch,Classic fit,Easy care',
                'description' => 'Classic straight-fit denim jeans built for daily wear.',
                'price' => 1590, 'old_price' => 1990, 'featured' => false, 'image' => 'products/jeans.jpg',
            ],
            [
                'name' => 'Embroidered Panjabi', 'slug' => 'embroidered-panjabi', 'sku' => 'GA-PJ-001',
                'cat' => 'Panjabi', 'sub' => 'Festive Edition', 'sizes' => 'M,L,XL,XXL', 'size_stock' => ['M' => 8, 'L' => 12, 'XL' => 7, 'XXL' => 3],
                'feat' => 'Fine embroidery,Soft fabric,Regular fit,Premium finish',
                'description' => 'Elegant panjabi with fine embroidery for Eid and festivities.',
                'price' => 1990, 'old_price' => 2500, 'featured' => true, 'image' => 'products/panjabi.jpg',
            ],
        ];

        foreach ($products as $data) {
            $sizeStock = $data['size_stock'];
            $data['category_id'] = $categoryIds[$data['cat']] ?? null;
            $data['stock'] = array_sum($sizeStock);
            $data['status'] = 'active';
            $data['images'] = [];

            Product::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $heroSlides = [
            ['path' => 'hero/slide-1.jpg', 'product_name' => 'New Season Collection', 'product_price' => 650, 'product_old_price' => 850, 'sort_order' => 0],
            ['path' => 'hero/slide-2.jpg', 'product_name' => 'Oxford Formal Shirt', 'product_price' => 1250, 'product_old_price' => 1550, 'sort_order' => 1],
            ['path' => 'hero/slide-3.jpg', 'product_name' => 'Slim Fit Chino Pant', 'product_price' => 1450, 'product_old_price' => 1800, 'sort_order' => 2],
            ['path' => 'hero/slide-4.jpg', 'product_name' => 'Embroidered Panjabi', 'product_price' => 1990, 'product_old_price' => 2500, 'sort_order' => 3],
        ];

        foreach ($heroSlides as $slide) {
            HeroImage::updateOrCreate(['path' => $slide['path']], $slide);
        }

        $reviews = [
            ['name' => 'Arif Hossain', 'text' => 'Fabric quality is excellent and delivery was fast. Highly recommended!', 'stars' => 5, 'slug' => 'classic-white-crew-tee'],
            ['name' => 'Sadia Rahman', 'text' => 'The shirt fits perfectly and looks even better in person.', 'stars' => 5, 'slug' => 'oxford-formal-shirt'],
            ['name' => 'Tanvir Ahmed', 'text' => 'Comfortable chino, great for office wear. Worth the price.', 'stars' => 4, 'slug' => 'slim-fit-chino-pant'],
            ['name' => 'Nusrat Jahan', 'text' => 'Loved the panjabi embroidery. Got many compliments on Eid.', 'stars' => 5, 'slug' => 'embroidered-panjabi'],
            ['name' => 'Rakib Hasan', 'text' => 'Good quality tee for everyday use. Color held up after washes.', 'stars' => 4, 'slug' => 'essential-black-tee'],
        ];

        foreach ($reviews as $review) {
            $productId = Product::where('slug', $review['slug'])->value('id');

            Review::firstOrCreate(
                ['name' => $review['name'], 'text' => $review['text']],
                ['stars' => $review['stars'], 'status' => 'approved', 'product_id' => $productId]
            );
        }

        Setting::put([
            'cats' => 'T-Shirt,Shirt,Pant,Panjabi',
            'hero_image' => 'hero/slide-1.jpg',
            'hero_transition' => 'slide',
            'hero_duration' => '5000',
            'hero_animation_speed' => '0.7',
            'hero_autoplay' => '1',
            'promo_title' => 'Eid Collection is Live',
            'promo_text' => 'Premium panjabis and shirts with cash on delivery all over Bangladesh.',
            'why_choose_us' => 'Shipped from Dhaka|Easy Returns|Quality Checked|Cash on Delivery',
            'about_images' => json_encode(['hero/slide-1.jpg', 'hero/slide-2.jpg', 'hero/slide-3.jpg']),
        ]);
    }
}
