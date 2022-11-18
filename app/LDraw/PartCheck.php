<?php

namespace App\LDraw;

use Illuminate\Support\Facades\Log;

use App\LDraw\FileUtils;
use App\LDraw\MetaData;

use App\Models\User;
use App\Models\PartType;

class PartCheck {
  public static function checkDescription($file = '') {
    return FileUtils::getDescription($file) !== false;
  }

  public static function checkLibraryApprovedDescription($file = '') {
    $desc = FileUtils::getName($file);
    return $desc !== false && preg_match('#^[\x20-\x7E\p{Latin}\p{Han}\p{Hiragana}\p{Katakana}\pS]+$#', $desc, $matches);
  }

  public static function checkName($file = '') {
    return FileUtils::getName($file) !== false;
  }

  public static function checkLibraryApprovedName($file = '') {
    $name = FileUtils::getName($file);
    return $name !== false && preg_match('#^[a-z0-9_\-]+(\.dat|\.png)$#', $name, $matches);
  }

  public static function checkNameAndPartType($file = '') {
    $name = FileUtils::getName($file);
    $type = FileUtils::getPartType($file);

    // Automatic fail if no Name:, LDRAW_ORG line, or DAT file has TEXTURE type
    if ($name === false || $type === false || stripos('Texture', $type['type']) !== false) return false;

    // Construct the name implied by the part type
    $folder = PartType::firstWhere('type', $type['type'])->folder;
    if (stripos('p/', $folder)) {
     $aname = substr($folder, stripos('p/', $folder) + 2) . $name;
    }
    else {
     $aname = substr($folder, stripos('parts/', $folder) + 6) . $name;
    }
    str_replace('/','\\', $aname);
    return $name === $aname;
  }

  public static function checkAuthor($file = '') {
    return FileUtils::getAuthor($file) !== false;
  }

  public static function checkAuthorInUsers($file = '') {
    $author = FileUtils::getAuthor($file);
    return $author !== false && !is_null(User::firstWhere([['name', '=', $author['name']],['realname', '=', $author['realname']]]));
  }

  public static function checkPartType($file = '') {
    return FileUtils::getPartType($file) !== false;
  }

  public static function checkLicense($file = '') {
    return FileUtils::getLicense($file) !== false;
  }

  public static function checkLibraryApprovedLicense($file = '') {
    $license = FileUtils::getLicense($file);
    $liblic = array_flip(MetaData::getLibraryLicenses());
    return $license !== false && isset($liblic[$license]) && $liblic[$license] !== 'NonCA';
  }

  public static function checkLibraryBFCCertify($file = '') {
    $bfc = FileUtils::getBFC($file);
    return $bfc !== false && !empty($bfc['certwinding'] && $bfc['certwinding'] === 'CCW');
  }

}
