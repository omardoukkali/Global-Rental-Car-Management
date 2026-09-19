<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Database\Seeder;

class CarImageSeeder extends Seeder
{
    /**
     * Real photos of each car model (Wikimedia Commons, free licence).
     * The first image of a car is the primary image.
     */
    public function run(): void
    {
        $photos = [            'Dacia Logan' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/3/3e/Dacia_Logan_Facelift_front_-_PSM_2009.jpg/960px-Dacia_Logan_Facelift_front_-_PSM_2009.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/7/7b/Dacia_Logan_III.jpg/960px-Dacia_Logan_III.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/b/bb/2011_Renault_Logan_Silverline_side.jpg/960px-2011_Renault_Logan_Silverline_side.jpg',
            ],
            'Dacia Sandero' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/5/59/Dacia_Sandero_III_1X7A6451.jpg/960px-Dacia_Sandero_III_1X7A6451.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/69/Dacia_Sandero_2023_Front_1.jpg/960px-Dacia_Sandero_2023_Front_1.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/19/Moscow%2C_Renault_Sandero_silver%2C_Sept_2025_01.jpg/960px-Moscow%2C_Renault_Sandero_silver%2C_Sept_2025_01.jpg',
            ],
            'Dacia Duster' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/2/2f/Dacia_Duster_III_IMG_8973.jpg/960px-Dacia_Duster_III_IMG_8973.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/d/d3/Renault_Duster_Techroad%2C_Natal_%28DSC05979%29.jpg/960px-Renault_Duster_Techroad%2C_Natal_%28DSC05979%29.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/05/Dacia_Duster_Facelift_%282017-present%29.jpg/960px-Dacia_Duster_Facelift_%282017-present%29.jpg',
            ],
            'Renault Clio' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/a4/Renault_Clio_R.S._Line_%28V%29_%E2%80%93_f_17102021.jpg/960px-Renault_Clio_R.S._Line_%28V%29_%E2%80%93_f_17102021.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/4/46/Renault_Clio_I_Phase_II_F%C3%BCnft%C3%BCrer_RN.JPG/960px-Renault_Clio_I_Phase_II_F%C3%BCnft%C3%BCrer_RN.JPG',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/2/29/Renault_Clio_RN.jpg/960px-Renault_Clio_RN.jpg',
            ],
            'Peugeot 208' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/d/d6/Peugeot_208_GTi_002.jpg/960px-Peugeot_208_GTi_002.jpg',
            ],
            'Peugeot 3008' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/ff/Peugeot_3008_20090706_front.JPG/960px-Peugeot_3008_20090706_front.JPG',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/4/41/PEUGEOT_3008_%28T84%29_China_%283%29.jpg/960px-PEUGEOT_3008_%28T84%29_China_%283%29.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/e/e1/Peugeot_3008_CN_Shishi_01_2022-06-10.jpg/960px-Peugeot_3008_CN_Shishi_01_2022-06-10.jpg',
            ],
            'Volkswagen Golf' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/5/5c/2017_Volkswagen_Golf_%285G_MY17%29_1.4_SE_TSI_hatchback_%282017-08-30%29.jpg/960px-2017_Volkswagen_Golf_%285G_MY17%29_1.4_SE_TSI_hatchback_%282017-08-30%29.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/4/4f/Volkswagen_Golf_VIII_R_1X7A7089.jpg/960px-Volkswagen_Golf_VIII_R_1X7A7089.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/8a/2020_Volkswagen_Golf_Style_1.5_Front.jpg/960px-2020_Volkswagen_Golf_Style_1.5_Front.jpg',
            ],
            'Toyota Corolla' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/f/f1/2018_Toyota_Corolla_%28MZEA12R%29_Ascent_Sport_hatchback_%282018-11-02%29_01.jpg/960px-2018_Toyota_Corolla_%28MZEA12R%29_Ascent_Sport_hatchback_%282018-11-02%29_01.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/4/46/Toyota_Corolla_AE111_1.6_GLi_Dark_Emerald_Green_Pearl_02.jpg/960px-Toyota_Corolla_AE111_1.6_GLi_Dark_Emerald_Green_Pearl_02.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c8/TOYOTA_COROLLA_%28E180%29_China_%282%29.jpg/960px-TOYOTA_COROLLA_%28E180%29_China_%282%29.jpg',
            ],
            'Hyundai Tucson' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/5/53/Hyundai_Tucson_%28NX4%2C_SWB%29_PHEV_1X7A1858.jpg/960px-Hyundai_Tucson_%28NX4%2C_SWB%29_PHEV_1X7A1858.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/d/d6/HYUNDAI_TUCSON%2C_iX35_%28LM%29_China_%2831%29.jpg/960px-HYUNDAI_TUCSON%2C_iX35_%28LM%29_China_%2831%29.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/01/HYUNDAI_TUCSON_%28TL%29_China_%2832%29.jpg/960px-HYUNDAI_TUCSON_%28TL%29_China_%2832%29.jpg',
            ],
            'Kia Picanto' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/1a/2004_Kia_Picanto_LX_1.1_Front.jpg/960px-2004_Kia_Picanto_LX_1.1_Front.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/16/Kia_Picanto_SA_1.1_LX_dlx_Orange.jpg/960px-Kia_Picanto_SA_1.1_LX_dlx_Orange.jpg',
            ],
            'Mercedes Classe C' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/1/18/Mercedes-Benz_W206_IMG_4869.jpg/960px-Mercedes-Benz_W206_IMG_4869.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/7/77/MERCEDES_BENZ_C-CLASS_%28W204%29_China_%284%29.jpg/960px-MERCEDES_BENZ_C-CLASS_%28W204%29_China_%284%29.jpg',
            ],
            'BMW Série 4' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/0/05/BMW_4_SERIES_COUPE_%28G22%29_China.jpg/960px-BMW_4_SERIES_COUPE_%28G22%29_China.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/6/6f/BMW_4_SERIES_GRAN_COUPE_%28F36%29_China_%282%29.jpg/960px-BMW_4_SERIES_GRAN_COUPE_%28F36%29_China_%282%29.jpg',
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/89/BMW_4_SERIES_COUPE_%28G22%29_China_%283%29.jpg/960px-BMW_4_SERIES_COUPE_%28G22%29_China_%283%29.jpg',
            ],
            'Ford Transit' => [
                'https://thumb.wikimedia.org/wikipedia/commons/thumb/8/88/2016_Ford_Transit_350_2.2.jpg/960px-2016_Ford_Transit_350_2.2.jpg',
            ],
        ];

        $cars = Car::all();

        foreach ($cars as $car) {
            $carPhotos = $photos[$car->brand . ' ' . $car->model];

            foreach ($carPhotos as $order => $url) {
                CarImage::factory()->create([
                    'car_id' => $car->id,
                    'url' => $url,
                    'is_primary' => $order === 0,
                    'display_order' => $order,
                ]);
            }
        }
    }
}