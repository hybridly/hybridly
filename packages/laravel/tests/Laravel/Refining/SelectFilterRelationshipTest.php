<?php

use Hybridly\Refining\Filters\SelectFilter;
use Hybridly\Tests\Fixtures\Database\Author;
use Hybridly\Tests\Fixtures\Database\AuthorFactory;
use Hybridly\Tests\Fixtures\Database\Book;
use Hybridly\Tests\Fixtures\Database\BookFactory;

it('can filter by relationship using basic configuration', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell']);
    $author2 = AuthorFactory::new()->create(['name' => 'Jane Austen']);

    $book1 = BookFactory::new()->create(['title' => '1984', 'author_id' => $author1->id]);
    $book2 = BookFactory::new()->create(['title' => 'Animal Farm', 'author_id' => $author1->id]);
    $book3 = BookFactory::new()->create(['title' => 'Pride and Prejudice', 'author_id' => $author2->id]);

    $books = mock_refiner(
        query: ['filters' => ['author' => ['value' => (string) $author1->id]]],
        refiners: [
            SelectFilter::make('author')->relationship('author', 'name'),
        ],
        classOrQuery: Book::class,
    )->get();

    expect($books)->count()->toBe(2);
    expect($books->pluck('title')->sort()->values()->all())->toBe(['1984', 'Animal Farm']);
});

it('can filter by relationship with custom column', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell', 'email' => 'george@orwell.com']);
    $author2 = AuthorFactory::new()->create(['name' => 'Jane Austen', 'email' => 'jane@austen.com']);

    BookFactory::new()->create(['title' => '1984', 'author_id' => $author1->id]);
    BookFactory::new()->create(['title' => 'Pride and Prejudice', 'author_id' => $author2->id]);

    $books = mock_refiner(
        query: ['filters' => ['author' => ['value' => (string) $author2->id]]],
        refiners: [
            SelectFilter::make('author')->relationship('author', 'email'),
        ],
        classOrQuery: Book::class,
    )->get();

    expect($books)
        ->count()
        ->toBe(1)
        ->first()
        ->title->toBe('Pride and Prejudice');
});

it('can filter by relationship with query modifier for loading options', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell']);
    $author2 = AuthorFactory::new()->create(['name' => 'Jane Austen']);
    $author2->delete(); // Soft delete - will be in options but filtering still respects relationship

    BookFactory::new()->create(['title' => '1984', 'author_id' => $author1->id]);
    BookFactory::new()->create(['title' => 'Pride and Prejudice', 'author_id' => $author2->id]);

    $refiner = mock_refiner(
        refiners: [
            SelectFilter::make('author')
                ->relationship('author', 'name', fn ($query) => $query->withTrashed())
                ->preload(5),
        ],
        classOrQuery: Book::class,
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    // Query modifier allows trashed authors to appear in options
    expect($metadata['options'])
        ->toHaveKey((string) $author1->id)
        ->toHaveKey((string) $author2->id); // Trashed author is included in options
});

it('returns no results when filtering by soft-deleted relationship without query modifier', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell']);
    $author2 = AuthorFactory::new()->create(['name' => 'Jane Austen']);
    $author2->delete(); // Soft delete

    BookFactory::new()->create(['title' => '1984', 'author_id' => $author1->id]);
    BookFactory::new()->create(['title' => 'Pride and Prejudice', 'author_id' => $author2->id]);

    $books = mock_refiner(
        query: ['filters' => ['author' => ['value' => (string) $author2->id]]],
        refiners: [
            SelectFilter::make('author')->relationship('author', 'name'),
        ],
        classOrQuery: Book::class,
    )->get();

    expect($books)->count()->toBe(0);
});

it('supports multiple selection with relationships', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell']);
    $author2 = AuthorFactory::new()->create(['name' => 'Jane Austen']);
    $author3 = AuthorFactory::new()->create(['name' => 'Leo Tolstoy']);

    BookFactory::new()->create(['title' => '1984', 'author_id' => $author1->id]);
    BookFactory::new()->create(['title' => 'Pride and Prejudice', 'author_id' => $author2->id]);
    BookFactory::new()->create(['title' => 'War and Peace', 'author_id' => $author3->id]);

    $books = mock_refiner(
        query: ['filters' => ['author' => ['value' => [(string) $author1->id, (string) $author2->id]]]],
        refiners: [
            SelectFilter::make('author')
                ->relationship('author', 'name')
                ->multiple(),
        ],
        classOrQuery: Book::class,
    )->get();

    expect($books)->count()->toBe(2);
    expect($books->pluck('title')->sort()->values()->all())->toBe(['1984', 'Pride and Prejudice']);
});

it('supports NOT_IN operator with multiple relationship selection', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell']);
    $author2 = AuthorFactory::new()->create(['name' => 'Jane Austen']);
    $author3 = AuthorFactory::new()->create(['name' => 'Leo Tolstoy']);

    BookFactory::new()->create(['title' => '1984', 'author_id' => $author1->id]);
    BookFactory::new()->create(['title' => 'Pride and Prejudice', 'author_id' => $author2->id]);
    BookFactory::new()->create(['title' => 'War and Peace', 'author_id' => $author3->id]);

    $books = mock_refiner(
        query: ['filters' => ['author' => ['value' => [(string) $author1->id, (string) $author2->id], 'operator' => 'not_in']]],
        refiners: [
            SelectFilter::make('author')
                ->relationship('author', 'name')
                ->multiple(),
        ],
        classOrQuery: Book::class,
    )->get();

    expect($books)
        ->count()
        ->toBe(1)
        ->first()
        ->title->toBe('War and Peace');
});

it('supports NOT_EQUALS operator with single relationship selection', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell']);
    $author2 = AuthorFactory::new()->create(['name' => 'Jane Austen']);

    BookFactory::new()->create(['title' => '1984', 'author_id' => $author1->id]);
    BookFactory::new()->create(['title' => 'Pride and Prejudice', 'author_id' => $author2->id]);

    $books = mock_refiner(
        query: ['filters' => ['author' => ['value' => (string) $author1->id, 'operator' => 'not_equals']]],
        refiners: [
            SelectFilter::make('author')->relationship('author', 'name'),
        ],
        classOrQuery: Book::class,
    )->get();

    expect($books)
        ->count()
        ->toBe(1)
        ->first()
        ->title->toBe('Pride and Prejudice');
});

it('provides relationship options in metadata', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell']);
    $author2 = AuthorFactory::new()->create(['name' => 'Jane Austen']);

    $refiner = mock_refiner(
        refiners: [
            SelectFilter::make('author')
                ->relationship('author', 'name')
                ->preload(5),
        ],
        classOrQuery: Book::class,
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata)
        ->toHaveKey('options')
        ->and($metadata['options'])
        ->toBeArray()
        ->toHaveKey((string) $author1->id)
        ->toHaveKey((string) $author2->id)
        ->and($metadata['is_searchable'])
        ->toBeTrue();
});

it('relationship filter is automatically searchable', function () {
    AuthorFactory::new()->create(['name' => 'George Orwell']);
    AuthorFactory::new()->create(['name' => 'Jane Austen']);

    $refiner = mock_refiner(
        refiners: [
            SelectFilter::make('author')->relationship('author', 'name'),
        ],
        classOrQuery: Book::class,
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata['is_searchable'])->toBeTrue();
});

it('can check if filter is relationship using isRelationship', function () {
    $filter = SelectFilter::make('author')->relationship('author', 'name');

    expect($filter->isRelationship())->toBeTrue();
});

it('returns false for isRelationship on non-relationship filters', function () {
    $filter = SelectFilter::make('author')->options(['1' => 'Author 1', '2' => 'Author 2']);

    expect($filter->isRelationship())->toBeFalse();
});

it('handles empty relationship filtering gracefully', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell']);

    BookFactory::new()->create(['title' => '1984', 'author_id' => $author1->id]);

    $books = mock_refiner(
        query: ['filters' => ['author' => ['value' => '999']]],
        refiners: [
            SelectFilter::make('author')->relationship('author', 'name'),
        ],
        classOrQuery: Book::class,
    )->get();

    expect($books)->count()->toBe(0);
});

it('works with query modifier that applies additional constraints', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell', 'email' => 'george@orwell.com']);
    $author2 = AuthorFactory::new()->create(['name' => 'Jane Austen', 'email' => 'jane@austen.com']);

    BookFactory::new()->create(['title' => '1984', 'author_id' => $author1->id]);
    BookFactory::new()->create(['title' => 'Pride and Prejudice', 'author_id' => $author2->id]);

    $books = mock_refiner(
        query: ['filters' => ['author' => ['value' => (string) $author1->id]]],
        refiners: [
            SelectFilter::make('author')
                ->relationship(
                    'author',
                    'name',
                    fn ($query) => $query->where('email', 'like', '%@orwell.com'),
                ),
        ],
        classOrQuery: Book::class,
    )->get();

    expect($books)
        ->count()
        ->toBe(1)
        ->first()
        ->title->toBe('1984');
});

it('includes empty relationship option label in metadata when enabled', function () {
    AuthorFactory::new()->create(['name' => 'George Orwell']);

    $refiner = mock_refiner(
        refiners: [
            SelectFilter::make('author')
                ->relationship('author', 'name', hasEmptyOption: true)
                ->emptyRelationshipOptionLabel('No author'),
        ],
        classOrQuery: Book::class,
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata)
        ->toHaveKey('empty_relationship_option_label', 'No author');
});

it('does not include empty relationship option label when not enabled', function () {
    AuthorFactory::new()->create(['name' => 'George Orwell']);

    $refiner = mock_refiner(
        refiners: [
            SelectFilter::make('author')->relationship('author', 'name'),
        ],
        classOrQuery: Book::class,
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata)
        ->not->toHaveKey('allows_empty_relationship_option')
        ->not->toHaveKey('empty_relationship_option_label');
});

it('supports closure for empty relationship option label', function () {
    AuthorFactory::new()->create(['name' => 'George Orwell']);

    $refiner = mock_refiner(
        refiners: [
            SelectFilter::make('author')
                ->relationship('author', 'name', hasEmptyOption: true)
                ->emptyRelationshipOptionLabel(fn () => 'Select an author'),
        ],
        classOrQuery: Book::class,
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata)
        ->toHaveKey('allows_empty_relationship_option', true)
        ->toHaveKey('empty_relationship_option_label', 'Select an author');
});

it('empty relationship option label is only set for relationships', function () {
    $refiner = mock_refiner(
        refiners: [
            SelectFilter::make('status')
                ->options(['active' => 'Active', 'inactive' => 'Inactive']),
        ],
        classOrQuery: Book::class,
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata)
        ->not->toHaveKey('allows_empty_relationship_option')
        ->not->toHaveKey('empty_relationship_option_label');
});

it('can use hasEmptyOption with query modifier', function () {
    $author = AuthorFactory::new()->create(['name' => 'George Orwell']);
    $author->delete();

    $refiner = mock_refiner(
        refiners: [
            SelectFilter::make('author')
                ->relationship(
                    'author',
                    'name',
                    fn ($query) => $query->withTrashed(),
                    hasEmptyOption: true,
                )
                ->emptyRelationshipOptionLabel('No author')
                ->preload(5),
        ],
        classOrQuery: Book::class,
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata)
        ->toHaveKey('allows_empty_relationship_option', true)
        ->toHaveKey('empty_relationship_option_label', 'No author')
        ->and($metadata['options'])
        ->toBeArray()
        ->toHaveKey((string) $author->id);
});

it('can filter for books without author when empty option is selected', function () {
    $author = AuthorFactory::new()->create(['name' => 'George Orwell']);

    BookFactory::new()->create(['title' => '1984', 'author_id' => $author->id]);
    BookFactory::new()->create(['title' => 'Unknown Book', 'author_id' => null]);
    BookFactory::new()->create(['title' => 'Another Unknown', 'author_id' => null]);

    $books = mock_refiner(
        query: ['filters' => ['author' => ['options' => ['empty' => true]]]],
        refiners: [
            SelectFilter::make('author')
                ->relationship('author', 'name', hasEmptyOption: true)
                ->emptyRelationshipOptionLabel('No author'),
        ],
        classOrQuery: Book::class,
    )->get();

    expect($books)->count()->toBe(2);
    expect($books->pluck('title')->sort()->values()->all())->toBe(['Another Unknown', 'Unknown Book']);
});

it('includes allows_empty_relationship_option metadata when enabled', function () {
    AuthorFactory::new()->create(['name' => 'George Orwell']);
    AuthorFactory::new()->create(['name' => 'Jane Austen']);

    $refiner = mock_refiner(
        refiners: [
            SelectFilter::make('author')
                ->relationship('author', 'name', hasEmptyOption: true)
                ->emptyRelationshipOptionLabel('No author')
                ->preload(5),
        ],
        classOrQuery: Book::class,
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata)
        ->toHaveKey('allows_empty_relationship_option', true)
        ->toHaveKey('empty_relationship_option_label', 'No author')
        ->and($metadata['options'])
        ->toHaveCount(2); // Only actual authors, no empty option
});

it('can filter with empty option combined with actual authors in multiple mode', function () {
    $author1 = AuthorFactory::new()->create(['name' => 'George Orwell']);
    $author2 = AuthorFactory::new()->create(['name' => 'Jane Austen']);

    BookFactory::new()->create(['title' => '1984', 'author_id' => $author1->id]);
    BookFactory::new()->create(['title' => 'Pride and Prejudice', 'author_id' => $author2->id]);
    BookFactory::new()->create(['title' => 'Unknown Book', 'author_id' => null]);
    BookFactory::new()->create(['title' => 'Another Book', 'author_id' => $author1->id]);

    $books = mock_refiner(
        query: ['filters' => ['author' => ['value' => [(string) $author1->id, null]]]],
        refiners: [
            SelectFilter::make('author')
                ->relationship('author', 'name', hasEmptyOption: true)
                ->emptyRelationshipOptionLabel('No author')
                ->preload(5)
                ->multiple(),
        ],
        classOrQuery: Book::class,
    )->get();

    expect($books)->count()->toBe(3);
    expect($books->pluck('title')->sort()->values()->all())
        ->toBe(['1984', 'Another Book', 'Unknown Book']);
});
