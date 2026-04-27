<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum LDrawRegex: string {
    case LineType0 = '~^\h*(?<linetype>0)\h+(?P<content>(?P<first_word>\S+)(?:\h+(?P<rest>.*))?)$~u';
    case LineType1 = '~^\h*(?<linetype>1)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<a>-?(?:\d*\.\d+|\d+))\h+(?<b>-?(?:\d*\.\d+|\d+))\h+(?<c>-?(?:\d*\.\d+|\d+))\h+(?<d>-?(?:\d*\.\d+|\d+))\h+(?<e>-?(?:\d*\.\d+|\d+))\h+(?<f>-?(?:\d*\.\d+|\d+))\h+(?<g>-?(?:\d*\.\d+|\d+))\h+(?<h>-?(?:\d*\.\d+|\d+))\h+(?<i>-?(?:\d*\.\d+|\d+))\h*(?<file>.+?)\h*$~u';
    case LineType2 = '~^\h*(?<linetype>2)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h*$~u';
    case LineType3 = '~^\h*(?<linetype>3)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h+(?<x3>-?(?:\d*\.\d+|\d+))\h+(?<y3>-?(?:\d*\.\d+|\d+))\h+(?<z3>-?(?:\d*\.\d+|\d+))\h*$~u';
    case LineType4 = '~^\h*(?<linetype>4)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h+(?<x3>-?(?:\d*\.\d+|\d+))\h+(?<y3>-?(?:\d*\.\d+|\d+))\h+(?<z3>-?(?:\d*\.\d+|\d+))\h+(?<x4>-?(?:\d*\.\d+|\d+))\h+(?<y4>-?(?:\d*\.\d+|\d+))\h+(?<z4>-?(?:\d*\.\d+|\d+))\h*$~u';
    case LineType5 = '~^\h*(?<linetype>5)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h+(?<x3>-?(?:\d*\.\d+|\d+))\h+(?<y3>-?(?:\d*\.\d+|\d+))\h+(?<z3>-?(?:\d*\.\d+|\d+))\h+(?<x4>-?(?:\d*\.\d+|\d+))\h+(?<y4>-?(?:\d*\.\d+|\d+))\h+(?<z4>-?(?:\d*\.\d+|\d+))\h*$~u';

    case Description = '#^\h*(?<linetype>0)\h+(?P<description>(?:(?P<prefix>[~_=|]+)\h*)?(?P<category>[^\h]+).*?)\h*$#u';

    case Name = '~^\h*(?<linetype>0)\h+Name:\h*(?P<name>[^\h]+)\h*$~u';
    case Basepart = '~^^(?<basepart>[uts]?\d+(?:[a-d][a-z]|[a-oq-z])?)~i';
    case SuffixValidate = '~(?<suffix>(?:(?:p[a-z0-9]{2,4}|c[0-9a-z]{2}|d[0-9a-z]{2}|k[0-9a-z]{2})+)?(?:-f[0-9a-z])?)$~i';
    case SuffixExtract = '~p(?:\d{4}|[cd][0-9a-z][0-9a-l]|[0-9a-z]{2})|c[a-z0-9]{2}|d[a-z0-9]{2}|k[0-9a-z]{2}|-f[0-9a-z]~i';

    case Author = '~^\h*(?<linetype>0)\h+Author:\h*(?:(?P<realname>[^\[]*?)\h*)?(?:\[(?P<username>[A-Za-z0-9_.-]+)\])?\h*$~u';

    case Ldraworg = '~^\h*(?<linetype>0)\h+!LDRAW_ORG\h+(?:(?P<unofficial>Unofficial)_?)?(?P<type>###PartTypes###)(?:\h+(?P<type_qualifier>###PartTypeQualifiers###))?(?:\h+(?P<release_type>ORIGINAL|UPDATE))?(?:\h+(?P<release>\d{4}-\d{2}))?\h*$~u';
    case LdconfigLdraworg = '~^\h*(?<linetype>0)\h+!LDRAW_ORG\h+Configuration\h+UPDATE\h+[0-9]{4}-[0-9]{2}-[0-9]{2}\h*~u';

    case Category = '~^\h*(?<linetype>0)\h+!CATEGORY\h+(?P<category>.*?)\h*$~u';
    case License = '~^\h*(?<linetype>0)\h+!LICENSE\h+(?P<license>.*?)\h*$~u';
    case Help = '~^\h*(?<linetype>0)\h+!HELP\h+(?P<help>.*?)\h*$~u';
    case Keywords = '~^\h*(?<linetype>0)\h+!KEYWORDS\h+(?P<keywords>.*?)\h*$~u';
    case Bfc = '~^\h*(?<linetype>0)\h+BFC\h+(?<bfc>NOCERTIFY|CERTIFY|CW|CCW|CLIP|NOCLIP|INVERTNEXT)(?:\h+(?<winding>CW|CCW))?\h*$~iu';
    case Cmdline = '~^\h*(?<linetype>0)\h+!CMDLINE\h+(?P<cmdline>.*?)\h*$~u';
    case Preview = '~^\h*(?<linetype>0)\h+!PREVIEW\h+(?<color>\d+)\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<rotation_matrix>(?<a>-?(?:\d*\.\d+|\d+))\h+(?<b>-?(?:\d*\.\d+|\d+))\h+(?<c>-?(?:\d*\.\d+|\d+))\h+(?<d>-?(?:\d*\.\d+|\d+))\h+(?<e>-?(?:\d*\.\d+|\d+))\h+(?<f>-?(?:\d*\.\d+|\d+))\h+(?<g>-?(?:\d*\.\d+|\d+))\h+(?<h>-?(?:\d*\.\d+|\d+))\h+(?<i>-?(?:\d*\.\d+|\d+)))\h*$~u';
    case History = '~^\h*(?<linetype>0)\h+!HISTORY\h+(?P<date>\d{4}-\d{2}-\d{2})\h+(?:\[(?P<username>[a-zA-Z0-9_.-]+)\]|\{(?P<realname>[^\}]+)\})\h+(?P<comment>.+?)\h*$~u';

    case Comment = '~^\h*(?<linetype>0)\h+\/\/(?:\h+(?P<comment>.*))$~u';
    case TexmapGeometry = '~^\h*(?<linetype>0)\h+!\:\h*(?P<tex_line>.+?)\h*$~u';
    case Texmap = '~^\h*(?<linetype>0)\h+!TEXMAP\h+(?P<command>START|NEXT|FALLBACK|END)(?:\h+(?P<method>PLANAR|CYLINDRICAL|SPHERICAL)\h+(?P<params>(?:[-+]?[0-9]*\.?[0-9]+\h+){8,10}[-+]?[0-9]*\.?[0-9]+)\h+(?P<file>\S+\.png)(?:\h+GLOSSMAP\h+(?P<glossfile>\S+\.png))?)?\h*$~u';
    case Colour = '~^0\h+!COLOUR\h+(?P<name>[A-Za-z0-9_]+)\h+CODE\h+(?P<code>\d+)\h+VALUE\h+(?P<value>(?:0x|#)[A-Fa-f0-9]{6})(?:\h+EDGE\h+(?P<edge>(?:\d+|(?:0x|#)[A-Fa-f0-9]{6})))(?:\h+ALPHA\h+(?P<alpha>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+LUMINANCE\h+(?P<luminance>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+(?P<material>(?:CHROME|PEARLESCENT|RUBBER|MATTE_METALLIC|METAL|MATERIAL)(?:\h+(?:CHROME|PEARLESCENT|RUBBER|MATTE_METALLIC|METAL|MATERIAL))*))?(?:\h+MATERIAL\h+(?P<material_params>.*))?$~u';
    case ColourMaterial = '~^(?P<material_type>GLITTER|SPECKLE|FABRIC)(?:\h+VALUE\h+(?P<value>(?:0x|#)[A-Fa-f0-9]{6}))?(?:\h+ALPHA\h+(?P<alpha>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+LUMINANCE\h+(?P<luminance>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+FRACTION\h+(?P<fraction>0(?:\.\d+)?|1(?:\.0+)?))?(?:\h+VFRACTION\h+(?P<vfraction>0(?:\.\d+)?|1(?:\.0+)?))?(?:\h+SIZE\h+(?P<size>(?:[1-9]\d*(?:\.\d+)?|0?\.\d*[1-9]\d*)))?(?:\h+MINSIZE\h+(?P<minsize>(?:[1-9]\d*(?:\.\d+)?|0?\.\d*[1-9]\d*))\h+MAXSIZE\h+(?P<maxsize>(?:[1-9]\d*(?:\.\d+)?|0?\.\d*[1-9]\d*)))?(?:\h+(?P<fabric_type>VELVET|CANVAS|STRING|FUR))?$~u';
    case Avatar = '~^\h*(?<linetype>0)\h+!AVATAR\h+CATEGORY\h+"(?P<category>[^"]+)"\h+DESCRIPTION\h+"(?P<description>[^"]+)"\h+PART\h+(?P<a>-?\d+(?:\.\d+)?)\h+(?P<b>-?\d+(?:\.\d+)?)\h+(?P<c>-?\d+(?:\.\d+)?)\h+(?P<d>-?\d+(?:\.\d+)?)\h+(?P<e>-?\d+(?:\.\d+)?)\h+(?P<f>-?\d+(?:\.\d+)?)\h+(?P<g>-?\d+(?:\.\d+)?)\h+(?P<h>-?\d+(?:\.\d+)?)\h+(?P<i>-?\d+(?:\.\d+)?)\h+"(?P<file>[^"]+)"$~u';

    public static function ldrawOrg(): string
    {
        return str_replace(
            ['###PartTypes###', '###PartTypeQualifiers###'],
            [
                implode('|', array_column(PartType::cases(), 'value')),
                implode('|', array_column(PartTypeQualifier::cases(), 'value'))
            ],
            self::Ldraworg->value
        );
    }

    public function type(): string
    {
        return Str::snake($this->name);
    }

    public function hasMatches(string $line): bool
    {
        return preg_match($this->value, $line) === 1;
    }

    public function match(string $line, &$matches = null): bool
    {
        return (bool) preg_match($this->value, $line, $matches, PREG_UNMATCHED_AS_NULL);
    }

    public function findIn(string $text, &$matches = null): bool
    {
        return collect(explode("\n", $text))->contains(function ($line) use (&$matches) {
            return $this->match(trim($line), $matches);
        });
    }
}
