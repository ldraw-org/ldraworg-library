# Part Header

This section defines the header format and the usage of the various header META commands.

> All text parts are required to conform to this document

It is strongly recommended that all other, non-library LDraw Library files conform to this document.

Since the rest of this section is almost all requirements for library parts, the blockquote format for requirements will not be used.



## Header Format

All text files are required to have a header in following format

```
0 <PartDescription>  
0 Name: Filename.dat  
0 Author: <RealName> [<UserName>]  
0 !LDRAW_ORG Part| Subpart| Primitive| 8_Primitive| 48_Primitive| Shortcut (optional qualifier(s)) ORIGINAL|UPDATE YYYY-RR  
or  
0 !LDRAW_ORG Unofficial_Part| Unofficial_Subpart| Unofficial_Primitive| Unofficial_8_Primitive| Unofficial_48_Primitive| Unofficial_Shortcut (optional qualifier(s))  
0 !LICENSE Licensed under CC BY 4.0 : see CAreadme.txt  
or  
0 !LICENSE Licensed under CC BY 2.0 and CC BY 4.0 : see CAreadme.txt  
or  
0 !LICENSE Redistributable under CCAL version 2.0 : see CAreadme.txt  
or  
0 !LICENSE Not redistributable : see NonCAreadme.txt  

0 !HELP <Optional free text description of file usage> 
0 !HELP <Second row after user's line break to simulate paragraph>

0 BFC ( CERTIFY ( CCW | CW ) | NOCERTIFY )

0 !CATEGORY <Category name>  
0 !KEYWORDS <words, more words,...,>  
0 !KEYWORDS <words in second row, ..., final words>

0 !CMDLINE LDraw run-time command(s)

0 !PREVIEW <colour> x y z a b c d e f g h i

0 !HISTORY YYYY-MM-DD [<UserName>] <Free text description of change. This can wrap to a> 
0 !HISTORY YYYY-MM-DD [<UserName>] <second row with the same date if necessary. However authors should lean toward writing longer>  
0 !HISTORY YYYY-MM-DD [<UserName>] <single !HISTORY lines(and not feel constrained to the historic 80-character limit on line length)>  
or  
0 !HISTORY YYYY-MM-DD {<RealName>} <Free text description of change>
```

## Required Commands

### `PartDescription`

#### General

This the descriptive name of the part. 

This command is always the first line of a part.  

The part description can contain any UTF-8 character with the exception of invisible control characters, unused code points, the line separator character (U+2028), and the paragraph separator character (U+2029). 

If the description contains dimensions, the numbers will be in decimal format (as opposed to fractions) and a leading space should be added to single digit numbers to aid in sorting. 
Decimals should be limited to 2 decimal places  

Examples:  
* `0 Brick  2 x  4`
* `0 Brick  1 x 10` (Note the lack of a leading space on the 2 digit number)
* `0 Brick  3.1 x  3.1`
* `0 Brick  3 x  3 x  0.34` (Note the 2 decimal places)

#### Needs Work

If the part is good enough for public use, but has some deficiencies that need to be addressed, the text " (Needs Work)" (without the quotation marks) can be added to the end of the description. 

If the description includes "(Needs Work)", a comment must be added to the file immediately after the header that explains the work that needs to be done


#### Special Prefix Characters

Some programs rely on the parts.lst file rather than the LDRAW_ORG meta-statement. Special prefix characters are added to the start of the part description to prevent the parts being included in the list by these programs:  

The descriptions of all subparts (i.e. line 4 = '0 !LDRAW_ORG Subpart') must start with '~'

The description of all obsolete parts, maintained for backwards compatibility must start with '~'

The use of '~' for non "s\" part files is at the author's discretion, to hide mouldings that are not released independently. This is one case where merely reading the LDRAW_ORG line is insufficient for tools to know how to treat the file.

The descriptions of all aliases (i.e. line 4 = '0 !LDRAW_ORG Part Alias') must start with '='

The descriptions of all third party parts (tXXXX.dat files) must start with '|' or '~|' (as appropriate)

**Depreciated** The descriptions of all physical colour parts (i.e. line 4 = '0 !LDRAW_ORG Part Physical_Colour') should start with '_'

### `Name:`

`Filename` is the file name of the part including extension.

If the file is not in the parts or p folder, the folder shall be included with a separator of '/'. For example 's/3001s01.dat' or '48/4-4cyli.dat'.

### `Author:`

`RealName` is the author's real name.

`UserName` is the author's LDraw username. It is optional for those authors that had parts released prior to the establishment of the Parts Tracker and have not contributed since

### `!LDRAW_ORG`

#### Official Parts

`Part| Subpart| Primitive| 8_Primitive| 48_Primitive| Shortcut| Helper` are used in 
  Official Library Parts  
`UPDATE YYYY-RR` is the LDraw update year and release within year.  `ORIGINAL` indicates that the part was released with the base LDraw library.

#### Unofficial Parts

`Unofficial_Part| Unofficial_Subpart| Unofficial_Primitive| Unofficial_8_Primitive| Unofficial_48_Primitive| Unofficial_Shortcut| Unofficial_Helper` are used in unofficial parts on the Parts Tracker.

#### Directory

Part will be located in certain subdirectories of the ldraw directory based on type:

* `Part`, `Shortcut`: parts
* `Subpart`: parts\s
* `Helper`: parts\helpers
* `Primitive`: p
* `8_Primitive`: p\8
* `48_Primitive`: p\48

#### Optional Qualifiers

The optional qualifier is one of the following:

* `Alias`  
    The purpose of the Alias qualifier is to identify a part which is visually identical to another (often caused by LEGO using two different numbers, one for opaque and one for transparent versions of the same part (e.g. 32061), but more recently, there have also been new part numbers for well-established parts - such as brick 2x6  
    An alias file will typically, but not necessarily, contain only one sub-file reference (line type 1) entry  
    A file with an Alias type qualifier must not refer to a file that itself has an Alias qualifier.  
* `Flexible_Section`  
    The purpose of the Flexible_Section qualifier is to identify a file in the "parts" folder which represents a subsection of a flexible part. The placement of such files in the "parts" folder allows LDraw users and LDraw applications (such as LSynth) to utilize the subsections to create a flexed rendition of the parent part. Ideally LDraw editing applications should use this qualifier to identify flexible part subsections.
* **Depreciated** `Physical_Colour`  
    This qualifier is depreciated and all parts that used it have been made obsolete.  This qualifier is documented for historical purposes only. No new parts will be released or submitted with this qualifier.

### `!LICENSE`

Can be one of the following:
* `0 !LICENSE Licensed under CC BY 4.0 : see CAreadme.txt`
* `0 !LICENSE Licensed under CC BY 2.0 and CC BY 4.0 : see CAreadme.txt`
* `0 !LICENSE Marked with CC0 1.0 : see CAreadme.txt`
* **Depreciated** `0 !LICENSE Redistributable under CCAL version 2.0 : see CAreadme.txt`
* **Depreciated** `0 !LICENSE Not redistributable : see NonCAreadme.txt`

The last 2 !LICENSE statements are documented for historical purposes only. No parts remain in the library and no new parts will be submitted with these statements.

### `!HISTORY`

Prior to official release, this command is required for any edits made to the part by a user other than the user listed in the author line
After official release, this command is required for all edits

# Optional Commands

### `!HELP`  

Used for helper text regarding file usage. Authors should constrain themselves to a 50-character limit to keep the text readable.

### `!CMDLINE`  

This META is defined for legacy purposes only and has no current use.

### `!PREVIEW <colour> x y z a b c d e f g h i`  
    
This META defines how a part's preview image (e.g. the image generated for the Library part detail page or in a BOM) color, position, and rotation should be modified from the standard view. The standard view is defined as the part's bounding box centered on the origin and the camera positioned at 30 lat, 45 long looking at the origin.  
`<colour> x y z a b c d e f g h i` are the equivalent to the same definition as [type 1 line](https://www.ldraw.org/article/218.html#lt1). Colours are limited to the colour numbers of the colours defined in the LDConfig.ldr  
Omitting this command from the header assumes a value of `16 0 0 0 1 0 0 0 1 0 0 0 1`

### `BFC`  

Usage is defined in [Back Face Culling (BFC) Language Extension](https://www.ldraw.org/article/415.html) 
 
### `!KEYWORDS` and `!CATEGORY`
    
Usage is defined in [LDraw.org CATEGORY and KEYWORDS Language Extension](https://www.ldraw.org/article/340)
