<?php
// Login now lives on /admin itself (shows the form when logged out).
header('Location: admin/', true, 302);
exit;
