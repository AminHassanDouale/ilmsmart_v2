<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IslamicBook;

class IslamicBooksSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [
                'title'        => "Student's Guide to Tajweed Rules",
                'author'       => 'Noha AsSersy',
                'category'     => 'quran',
                'description'  => 'A comprehensive student guide to the rules of Tajweed (proper Quranic recitation), covering makharij al-huruf, sifat, and practical recitation rules.',
                'language'     => 'English',
                'file_path'    => "public-files:Student's Guide to Tajweed rules ..Noha AsSersy.pdf",
                'is_published' => true,
                'sort_order'   => 1,
            ],
            [
                'title'        => 'القاعدة البغدادية',
                'author'       => null,
                'category'     => 'arabic',
                'description'  => 'القاعدة البغدادية — أحد أشهر الكتب التعليمية لتعلم القراءة العربية والقرآن الكريم للمبتدئين.',
                'language'     => 'Arabic',
                'file_path'    => 'public-files:القاعدة البغدادية (1).pdf',
                'is_published' => true,
                'sort_order'   => 2,
            ],
            [
                'title'        => 'القاعدة النورانية',
                'author'       => 'الشيخ نور محمد حقاني',
                'category'     => 'quran',
                'description'  => 'القاعدة النورانية — المنهج المشهور لتعليم قراءة القرآن الكريم بالتجويد، يستخدم على نطاق واسع في المدارس الإسلامية حول العالم.',
                'language'     => 'Arabic',
                'file_path'    => 'public-files:القاعدة النورانية.pdf',
                'is_published' => true,
                'sort_order'   => 3,
            ],
        ];

        foreach ($books as $book) {
            IslamicBook::firstOrCreate(
                ['title' => $book['title']],
                $book
            );
        }

        echo "  Seeded " . count($books) . " Islamic books from public/files/\n";
    }
}
