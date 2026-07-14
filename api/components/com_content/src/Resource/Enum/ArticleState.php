<?php

namespace Joomla\Component\Content\Api\Resource\Enum

enum ArticleState: int
{
    case Published = 1;
    case Unpublished = 0;
    case Trashed = -2;
    case Archived = 2;
}
