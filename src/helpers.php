<?php

namespace "Jh-83\\Iter";


/** no reporting if/when an exception is thrown */
function rescueQuietly(callable $try, ?callable $catch = null)
{
    return rescue($try, $catch, false);
}

function toIterable(mixed $val): iterable
{
    return match (TRUE) {
        is_iterable($val) => $val,
        default => [$val],
    };
}
