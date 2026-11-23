<?php

namespace App\Models;

// Keep the database factory name while sharing one account model for auth and roles.
class_alias(User::class, __NAMESPACE__.'\\UserAccount');
