<?php

// test('a filter can be inlined', function () {
//     CompanyFactory::new()->count(10)->create();
//     CompanyFactory::new()->create([
//         'name' => 'Taylor Otwell',
//     ]);

//     mock_refine_request(fn (InputBag $query) => $query->set('last_name', 'Otwell'));

//     $filters = Refine::model(Company::class)->with([
//         CallbackFilter::make('has_posts', function (Builder $query, mixed $value) {
//             return $query->whereHas('posts');
//         }),
//         ExactFilter::make('last_name', aliases: ['nickname']),
//         TrashedFilter::make(),
//         SimilarityFilter::make('name'),
//         // Sort::make('created_at', aliases: ['created'], default: true),
//     ]);

//     dd($filters);
// });
