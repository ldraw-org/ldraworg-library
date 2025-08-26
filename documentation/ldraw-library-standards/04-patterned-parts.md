# Patterned, Dual Moulded, and TEXMAP Parts

## Introduction

Patterned parts are parts with ink printing on the base material (usually plastic). Patterned parts are generally modeled using geometry that set to a specific color (as opposed to color 16). Patterned parts can also be modeled using image files and the !TEXMAP command (see [Texture Mapping (!TEXMAP) Language Extension](https://www.ldraw.org/texmap-spec.html)).

Additionally, for the LDraw library, parts that are dual moulded are also considered to be part of the patterned part class.

## General

Some patterned parts have never been issued as plain, patternless parts by LEGO. This printing is easily removed, and LDraw parts can come in any variety. For the purposes of this document, this plain, patternless part will be referred to as the **base part**.

> All patterned parts must have a base part even if a plain version has never been produced by LEGO.

## Header

### Part Description

> The description of a patterned part should in the following format:  
> `<base part description> with <pattern description> Pattern`

`<base part description>` is the description of the non-patterned version of the part. 

**Note:** For parts with mould variants (e.g. tiles with or without groove), the additional qualifier for the mould is only required if the same pattern is printed on more than one variation. Example: "Minifig Head with Solid Stud with ... Pattern" can be shortened to "Minifig Head with ... Pattern" if the patterned being modeled only appears on the "Solid Stud" variation.

`<pattern description>` is a reasonably detailed description of the pattern. If unsure, use of the description on other inventory websites is encouraged.

> For dual moulded parts, the final word `Pattern` is omitted and the  keyword **Colour Combination** added.

### `!KEYWORDS`

> All patterned parts shall have at least one of the following keywords: **Set `<Set Number>`**, **CMF** (For Collectible Minifig), or **Build-A-Minifigure**.

If the set is unknown and the other keywords are inappropriate, the keyword can be **Set Unknown**. The **CMF** keyword may include the series number (e.g. **CMF Series 12**). The keyword **Colour Combination** is used for dual moulded part per the Part Descricption section.
 
 ## Number

> The number for a patterned part shall be `<base part number>pXX(X)` or `<base part number>pNNNN`

`pXX(X)` is a 2 (or 3) character code where each character is 0-9a-z. 

> Only codes starting with **c** or **d** may be 3 characters long and are reserved exclusively for patterns in the Collectible Minifig series

**cXY** codes are for number CMF series, X is the series number (1=1 ... a=11 ... z=36) amd Y is the sequence 1-9,a-h (a=10...h=17). 

> For series numbers above 36, the **pNNNN** format shall be used

**dXY** codes are for unnumbered CMF series. Y is the same as above. X is the following:

* X=1 for Simpsons series 1; Y=1-9,a-g
* X=2 for The LEGO Movie; Y=1-9,a-g
* X=3 for Simpsons series 2; Y=1-9,a-g
* X=4 for Disney; Y=1-9,a-i
* X=5 for 2016 German Football Team; Y=1-9,a-g
* X=6 for The LEGO Batman Movie; Y=1-9,a-k
* X=7 for The LEGO Ninjago Movie; Y=1-9,a-k
* X=8 for The LEGO Batman Movie series 2; Y=1-9,a-k
* X=9 for Wizarding World; Y=1-9, a-m
* X=a for The LEGO Movie 2; Y=1-9, a-k
* X=b for Disney series 2; Y=1-9, a-i
* X=c for DC Super Heroes; Y=1-9,a-g
* X=d for Harry Potter series 2; Y=1-9,a-g
* X=e for Looney Tunes; Y=1-9,a-c
* X=f for Marvel Studios; Y=1-9,a-c
* X=g for The Muppets; Y=1-9,a-c
* X=h for Disney 100 Years; Y=1-9,a-i
* X=i for Marvel Studios series 2; Y=1-9,a-c
* X=j for 2012 Team GB; Y=1-9

> Any other unnumbered series, the **pNNNN** format shall be used

`pNNNN` is a 4 digit number with leading zeros starting a 0001. 

> The **pXX(X)** shall be perferentially used. Once all available codes for a given base part are used (i.e. p00 - pzz), the first available number in the **pNNNN** format shall be used for all further patterned parts

## `!TEXMAP`

### Number

> The number for TEXMAP image shall be `<pattern number><a-z>.png`

If there is only one TEXMAP for the part then the a-z suffix is not required.

### Geometry

> Sufficient !TEXMAP \<geometry2\> and/or \<geometry3\> lines (as outlined in the [Texture Mapping (!TEXMAP) Language Extension](https://www.ldraw.org/texmap-spec.html)) must be included such that the part renders correctly in non-!TEXMAP supporting programs.
 
> A fallback pattern, if defined, must represent the pattern created by the TEXMAP image.

It is highly encouraged, but not required, that a fallback pattern be defined even if it is reduced in resolution or colors.
