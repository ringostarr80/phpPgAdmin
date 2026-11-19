<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects\Tablespace;

use PhpPgAdmin\DDD\ValueObjects\Tablespace\Comment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comment::class)]
final class CommentTest extends TestCase
{
    public function testTheBlanksAndLineBreaksAroundACommentArePartOfIt(): void
    {
        // PostgreSQL stores a comment as it is given.
        self::assertSame("  Old data\n", (string)new Comment("  Old data\n"));
    }
}
