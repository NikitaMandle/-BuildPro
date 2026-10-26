<?php
// Site root opens the admin panel (login form shows there if not logged in).
header('Location: admin/', true, 302);
exit;
