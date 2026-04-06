<?php
// Basit PHP cookie testi — Laravel dışında
setcookie('test_plain', 'works', time() + 3600, '/', '', true, true);
setcookie('test_nosecure', 'works2', time() + 3600, '/', '', false, true);

echo "Cookies sent. Reload page and check below:\n\n";
echo "Received cookies:\n";
print_r($_COOKIE);
