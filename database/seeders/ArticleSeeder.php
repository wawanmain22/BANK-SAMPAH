<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@banksampah.test')->first();
        $authorId = $admin?->id;

        $articles = [
            [
                'title' => 'Kenapa Memilah Sampah dari Rumah Itu Penting?',
                'excerpt' => 'Dengan memilah sampah sejak dari rumah, kita membuat proses daur ulang jadi jauh lebih efektif.',
                'content' => "Memilah sampah dari rumah adalah langkah awal paling sederhana namun paling berdampak dalam rantai daur ulang.\n\nKetika sampah organik, anorganik, dan residu bercampur, nilai ekonomi dan ekologisnya turun drastis. Sampah yang sudah tercemar limbah organik sulit diproses ulang menjadi produk bernilai.\n\nMulailah dengan 3 tempat sampah sederhana di rumah: organik, anorganik kering, dan residu. Setelah terbiasa, kontribusi Anda akan signifikan.",
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Plastik PET vs HDPE: Apa Bedanya?',
                'excerpt' => 'Dua jenis plastik yang paling sering kita temui sehari-hari punya nilai daur ulang yang beda.',
                'content' => "Plastik PET (Polyethylene Terephthalate) biasa ditemui pada botol air mineral — jernih, ringan, dan mudah didaur ulang jadi serat tekstil.\n\nHDPE (High-Density Polyethylene) lebih tebal dan buram, contohnya galon air dan botol sabun. HDPE bisa dilebur dan dicetak ulang jadi pot, papan plastik, atau paving block.\n\nMemisahkan keduanya saat setor ke bank sampah membuat harga per kg jadi lebih optimal.",
                'published_at' => now()->subDays(10),
            ],
            [
                'title' => 'Dari Sampah Organik ke Kompos: Panduan Singkat',
                'excerpt' => 'Pelajari cara mengubah sisa dapur jadi pupuk berkualitas untuk tanaman di rumah.',
                'content' => "Sampah organik rumah tangga bisa diubah jadi kompos dalam 30-45 hari dengan metode sederhana.\n\n1. Siapkan ember kompos atau komposter dengan lubang drainase.\n2. Lapisi dasar dengan ranting/daun kering (coklat).\n3. Tambahkan sisa sayur, buah, dan ampas kopi (hijau) — perbandingan 1:2 hijau:coklat.\n4. Aduk setiap 3-5 hari, jaga kelembapan seperti spons basah.\n5. Setelah 1 bulan+ warnanya gelap, berbau tanah: siap dipakai.",
                'published_at' => now()->subDays(18),
            ],
            [
                'title' => 'Ekonomi Sirkular: Kenapa Sampah Adalah Aset',
                'excerpt' => 'Paradigma baru yang memandang sampah bukan beban tapi sumber daya.',
                'content' => "Ekonomi sirkular adalah model ekonomi di mana produk, bahan, dan sumber daya dipakai seefisien mungkin, lalu dikembalikan ke siklus produksi — bukan dibuang.\n\nBank sampah adalah perwujudan konkret ekonomi sirkular di level komunitas. Sampah yang dulu dibuang kini punya nilai uang yang mengalir kembali ke masyarakat.\n\nSemakin banyak yang sadar, semakin besar dampak lingkungan dan ekonomi yang bisa diciptakan.",
                'published_at' => now()->subDays(25),
            ],
            [
                'title' => 'Tips Mengurangi Sampah Plastik Sekali Pakai',
                'excerpt' => '5 kebiasaan praktis yang bisa Anda mulai hari ini juga.',
                'content' => "1. Bawa tumbler — tolak botol plastik sekali pakai.\n2. Gunakan tas belanja kain — kantong plastik kresek adalah pencemar utama.\n3. Pilih produk dengan kemasan minimal atau refill.\n4. Ganti sedotan plastik dengan sedotan stainless atau bambu.\n5. Belanja sayur ke pasar tradisional bawa wadah sendiri.\n\nSatu perubahan kecil dari tiap orang, jika dikalikan, berdampak besar.",
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => '[Draft] Rencana Lomba Bank Sampah RT 2026',
                'excerpt' => 'Draft artikel untuk lomba mendatang.',
                'content' => 'Detail belum final.',
                'published_at' => null,
            ],
        ];

        $demoImage = '/images/demo/edukasi.jpg';

        foreach ($articles as $data) {
            Article::firstOrCreate(
                ['title' => $data['title']],
                [
                    ...$data,
                    'slug' => Str::slug($data['title']).'-'.Str::random(4),
                    'author_id' => $authorId,
                    'featured_image' => $demoImage,
                    'images' => [$demoImage, $demoImage, $demoImage],
                ],
            );
        }
    }
}
