<?php

// Everything here was typed by customers, so the template escapes every string with @html().
$stars = static fn (int $rating): string => str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

return [
    'reviews' => [
        [
            'author' => 'Hana K.',
            'created_at' => strtotime('2026-09-14'),
            'verified' => true,
            'rating' => 5,
            'stars' => $stars(5),
            'body' => 'Pours without a drip. The <b>filter</b> is fine enough for sencha & gyokuro.',
        ],
        [
            'author' => 'Tom "the taster"',
            'created_at' => strtotime('2026-09-02'),
            'verified' => false,
            'rating' => 2,
            'stars' => $stars(2),
            'body' => '<script>alert("hacked")</script> Lid rattles a bit.',
        ],
    ],
];
