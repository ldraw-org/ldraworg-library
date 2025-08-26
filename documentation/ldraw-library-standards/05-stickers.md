# Stickers

## Introduction

## Header

### Part Description

> The description of a sticker shall start with the word **Sticker** followed by the dimensions if rectangular or round, or a description of the shape, and then a description of the sticker. 

> The dimensions, if supplied, shall be specified in studs (where 1 stud = 20 LDraw units) and as [z-dimension] x [x-dimension]

### `!KEYWORDS`

> All stickers shall have a **Set `<Set Number>`** keyword. If the set is unknown the keyword shall be **Set Unknown**

## Number

### Sticker Sheets with Numbers
Official LEGO numbering for sticker sheets has changed over time

* no copyright date sheets - 4-digit numbers: 3xxx, 4xxx or 6-digit numbers: 004xxx
* no copyright date sheets - 6-digit numbers: 168xxx, 169xxx, 197xxx, 820xxx, 821xxx
* &copy;1993 / 1994 - 6-digit numbers: 168xxx, 169xxx, 822xxx; a few 7-digit numbers: 4100xxx 
* &copy;1995 - 2000 - combo 5-digit/7-digit numbers: 71xxx/41xxxxx, 72xxx/41xxxxx
* &copy;2000 - 2003 - combo 5-digit/7-digit numbers: 2xxxx/41xxxxx, 4xxxx/41xxxxx, 4xxxx/42xxxxx; a few 7-digit numbers: 42xxxxx
* &copy;2004 onwards - combo 5-digit/7-digit numbers: 5xxxx/4xxxxxx, 6xxxx/4xxxxxx, 7xxxx/4xxxxxx, 8xxxx/4xxxxxx </LI>
* &copy;2012 onwards - combo 5-digit/7-digit numbers: 1xxxx/60xxxxx </

> Sticker parts numbering shall be `<sticker sheet number><a-z or aa-zz>.dat`. **Note:** the suffix is not optional and must be included for sheet with a single sticker.

> If the stickers are numbered on the sticker sheet, the printed sticker number and the character suffix should correspond to the sequence where a=1, b=2, ... , z=26, aa=27, ab=28, etc.

> Four digit numbers shall be prefixed with two zeros to avoid clashes with LEGO parts (e.g. 004845a.dat, not 4845a.dat).

> Stickers from sheets with combo 5-digit/7-digit numbers shall use the 7-digit number.


### Sticker Sheets with No Number

> Stickers from sheets with no printed number or from parts with pre-applied stickers shall be `s<number>.dat` where number is the first available number starting at 1.


### Formed Stickers

> Formed sticker numbering shall be `<flat sticker number>cXX.dat` where XX is a 2 character code where each character is 0-9a-z starting at 01

## Geometry

> Stickers shall be created flat and oriented such that the top face (where the pattern is located) is at -0.25 Y and parallel with the X-Z plane.

> The sticker shall be modeled as a 0.25 LDU thin box (or whatever shape sticker is in), without any edge lines (type 2 lines), and coloured with main colour 16. Conditional lines (type 5 lines), shall be used around curved edges, as appropriate. 

> Formed stickers shall have conditional lines (type 5 lines) on the curved  top surface of formed sticker parts.  Solid edges (type 2 lines) are also allowed for folds in formed sticker parts, but not around the edges of the sticker.

Modern stickers have rounded instead of sharp cut corners. 

> The radius of standard rounded corners will be 1.5 LDU.

For most modern stickers, the surface of the sticker is intended to be applied to an entire face of a single part. 

> In this case, the size of the sticker should be that the edge of the sticker is 2 ldu from the edge of the part face. If actual, measured sticker size is +/- 1 ldu from this standard then the sticker should be modeled to the standard. 

Exceptions to this rule will be handled on a case by case basis and should be specifically addressed by the author upon submission.

### Origin/Orientation

#### Flat Stickers

> Flat sticker X-Z origin shall be the center of the top face.

> Flat Stickers shall be oriented so that top is aligned with +Z axis. Top is defined as either the logical top of a picture, text, etc. or, in the case of more abstract patterns, what the top is when the sticker is applied. If ambiguous, sticker should be oriented such that X value is greater that Z (Landscape).

![Sticker Orientation](https://www.ldraw.org/uploads/images/Articles/sticker_orientation.png)

#### Formed Stickers

> Formed sticker origin shall match that of the part for which it is intended or be noted in a !HELP header line.

### Top pattern

> If the sticker is printed on an opaque medium, the top, patterned face of the sticker shall be modeled in it's true colors 

For example if the paper the sticker is printed on is white, the top face will be the pattern and use white geometry to fill in the rest of the space.

> If the sticker is printed on a transparent medium, the transperant portions of the top, patterned face shall use color 16. Any non-color 16 geometry shall be enclosed between `0 BFC NOCLIP` and `0 BFC CLIP` statements. If no color 16 geomtry follows the non-color 16 geometry, the `0 BFC CLIP` statement may be omitted. See [Language Extension for Back Face Culling (BFC)](https://www.ldraw.org/article/415.html)

### Backing Box

The backing box is defined as the sides and bottom surface of the sticker

> A discrete sticker back file is required for all square/rectangluar with standard rounded corners. 

Sticker back files are optional for all other sticker shapes but encouraged if that shape and size is commonly reused.

> Sticker back files will be of the !LDRAW_ORG type of Subpart and will located in the parts/s/ directory.

> The backing boxes for older, sharp cornered stickers should be modeled with an appropriately scaled box5-12.

> Circular and oval shaped stickers should use an appropriately scaled 4-4cylc3

> Stickers with irregular shapes should use quads and triangles with conditional lines where appropriate.

#### Flat Sticker Back Files

> All sticker back files shall be oriented such that the X width is greater with than the Z width (landscape format).

> Flat sticker back files shall use the following numbering: `stickerback<ZZZ>x<XXX><optional suffix a-z>.dat`

`<ZZZ>` and `<XXX>` are a 3 digit numbers representing the Z width (for ZZZ) and X width (for XXX) of the sticker. The value of this number is LDU/20 rounded to the nearest tenth. A leading zeros will be added for a number less than 10. Example: if the Z width of the sticker is 143 ldu, ZZZ is 072.

> Sticker back files without the optional suffix are reserved for the square/rectangluar sticker backs with standard rounded corners. 

> Sticker backs of any other shape with the same dimensions shall use an optional suffix `<a-z>`.

#### Formed Sticker Back Files

Formed sticker back files should be created as reuse dictates.

> Formed sticker back file shall use the following numbering: `<part number>bNN.dat`

`<part number>` is the number of the part to which the sticker is formed and NN is a 2 digit number with leading zeros starting at 01

## Sticker Shortcuts

Sticker shortcuts are a combination of one or more stickers and a single part.

The single part the sticker(s) are applied to is referred to here as the **base part**

> Sticker shortcuts shall:
>
> * Consist of one part and one or more stickers
> * Appear as a combination in one or more official sets released by LEGO. The stickers shall be applied in the position and orientation shown in that set(s) instructions
> * Have a part description starting with  `<base part description> with <sticker description> sticker`
> * Have `!LDRAW_ORG` type `Shortcut`
> * Have the category "Sticker Shortcut"
> * Contain at least one `Set <Set Number>` keyword
> * The base part shall be color 16. The sticker shall be color 16 unless the sticker is applied to a portion of the base part that is colored a transparent colors in which case the sticker shall be the color of the medium on which it is printed (e.g. white, transparent sticker, chrome, etc).
> * Use following numbering: `<base part>dXX.dat` where `dXX` is a 2 character code starting with '01' and ending with 'zz'. The first available code in sequence shall be used.
