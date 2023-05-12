<?php
/**
 * A filter that will filter out any query whose clause is not of a type.
 *
 * @package lucatume\WPBrowser\Iterators\Filters
 */

namespace lucatume\WPBrowser\Iterators\Filters;

use Iterator;
use lucatume\WPBrowser\Utils\Strings;

/**
 * Class MainStatementQueriesFilter
 *
 * @package lucatume\WPBrowser\Iterators\Filters
 */
class MainStatementQueriesFilter extends \FilterIterator
{
    /**
     * MainStatementQueriesFilter constructor.
     *
     * @param Iterator<string> $iterator
     * @param string $statement The statement to keep queries for.
     */
    public function __construct(Iterator $iterator, protected $statement = 'SELECT')
    {
        parent::__construct($iterator);
    }

    /**
     * Check whether the current element of the iterator is acceptable
     *
     * @link http://php.net/manual/en/filteriterator.accept.php
     *
     * @return bool true if the current element is acceptable, otherwise false.
     */
    public function accept(): bool
    {
        /** @var array{0: string, 1: int, 2: string} $query */
        $query = $this->getInnerIterator()->current();
        $pattern = Strings::isRegex($this->statement) ? $this->statement : '/^' . $this->statement . '/i';
        /** @noinspection NotOptimalRegularExpressionsInspection */
        if (!preg_match($pattern, $query[0])) {
            return false;
        }

        return true;
    }
}
