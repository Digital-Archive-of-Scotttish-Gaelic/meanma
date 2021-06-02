<?php


namespace models;


class glygature
{
	private $_glygatures = <<<XML
			<xml>
				<glyph id="g0">
          <glyphName>UNKNOWN</glyphName>
          <note>The scribal abbreviation is unknown. This will probably be because the manuscript is
            now illegible and an edition is being used. The editor will have indicated that an
            abbreviation has been expanded but given no details.</note>
        </glyph>
        <glyph id="g1" corresp="https://www.vanhamel.nl/codecs/M_stroke">
          <glyphName>m-stroke</glyphName>
          <note>A horizontal line with a downwards hook on the right-hand side, most frequently
            standing for "-m", or, occasionally "m" with preceding or succeeding letter(s).</note>
        </glyph>
        <glyph id="g2" corresp="https://www.vanhamel.nl/codecs/Ocus,_et_(Tironian_note)">
          <glyphName>Tironian Nota</glyphName>
          <note>Resembles a "7" and may stand for "ocus" and variants or "et" and variants.</note>
        </glyph>
        <glyph id="g3" corresp="https://www.vanhamel.nl/codecs/Suspension_stroke">
          <glyphName>suspension stroke</glyphName>
          <note>A superscript or mid-level horizontal stroke that may stand for any
            character(s).</note>
        </glyph>
        <glyph id="g4" corresp="https://www.vanhamel.nl/codecs/Punctum_delens_(lenition)">
          <glyphName>lenition marker</glyphName>
          <note>May appear as a superscript punctum, spiritus asper or breve; adds an "-h" to
            consonants (i.e. lenites).</note>
        </glyph>
        <glyph id="g5" corresp="https://www.vanhamel.nl/codecs/Suspension_stroke">
          <glyphName>n-stroke</glyphName>
          <note>A horizontal superscript line, standing for "n". Often indistinguishable from a
            generic suspension stroke.</note>
        </glyph>
        <glyph id="g6" corresp="https://www.vanhamel.nl/codecs/I_(superscript_rounded)">
          <glyphName>superscript rounded i</glyphName>
          <note>An anti-clockwise loop on top of a consonant, frequently representing "ir" but with
            some variability in the vowel.</note>
        </glyph>
        <glyph id="g7" corresp="https://www.vanhamel.nl/codecs/Us_(symbol)">
          <glyphName>us symbol</glyphName>
          <note>Resembles a semi-colon (;) and generally stands for "us" but with some variability
            in the vowel.</note>
        </glyph>
        <glyph id="g8" corresp="https://www.vanhamel.nl/codecs/Ar_(with_stroke)">
          <glyphName>ar symbol</glyphName>
          <note>An "a" with a descender on the right-hand side (very like "q"), with a cross-stroke
            on the descender, standing for "ar".</note>
        </glyph>
        <glyph id="g9"
          corresp="https://www.vanhamel.nl/codecs/Enclosing_punctus_(critical_sign)">
          <glyphName>enclosing puncta</glyphName>
          <note>An punctum both before and after a letter or set of letters that may stand for any
            character(s).</note>
        </glyph>
        <glyph id="g10"
          corresp="https://www.vanhamel.nl/codecs/S_(insular)_with_suspension_stroke">
          <glyphName>s with suspension stroke (acht/sed)</glyphName>
          <note>An "s" with a horizontal superscript suspension stroke. Originally for Latin "sed"
            but used to represent "(e/a/ea)ch(t/d)" in Gaelic contexts.</note>
        </glyph>
        <glyph id="g11" corresp="https://www.vanhamel.nl/codecs/Air_(symbol_with_superscript)">
          <glyphName>air symbol</glyphName>
          <note>An "a" with a descender on the right-hand side (very like "q"), with a cross-stroke
            on the descender, and superscript "i" standing for "air".</note>
        </glyph>
        <glyph id="g12" corresp="https://www.vanhamel.nl/codecs/C_(superscript)">
          <glyphName>superscript c</glyphName>
          <note>A superscript "c", usually standing for "c" preceded by a vowel and/or followed by
            "h".</note>
        </glyph>
        <glyph id="g13" corresp="https://www.vanhamel.nl/codecs/Arr_(double_stroke)">
          <glyphName>arr symbol</glyphName>
          <note>An "a" with a descender on the right-hand side (very like "q"), with two
            cross-strokes on the descender, standing for "arr".</note>
        </glyph>
        <glyph id="g14" corresp="https://www.vanhamel.nl/codecs/Suspension_stroke_(double)">
          <glyphName>double n-stroke</glyphName>
          <note>Two horizontal superscript or mid-level lines, standing for "nn".</note>
        </glyph>
        <glyph id="g15" corresp="https://www.vanhamel.nl/codecs/O_(superscript)">
          <glyphName>superscript o</glyphName>
          <note>A superscript "o", usually standing for "or" or "ro".</note>
        </glyph>
        <glyph id="g16">
          <glyphName>ur abbreviation</glyphName>
          <note>A superscript u/v, or wavy superscript stroke, usually standing for "u(i)r" or
            "ru".</note>
        </glyph>
        <glyph id="g17" corresp="https://www.vanhamel.nl/codecs/A_(superscript)">
          <glyphName>superscript a</glyphName>
          <note>A superscript "a", usually standing for "ra" or "ra".</note>
        </glyph>
        <glyph id="g18" corresp="https://www.vanhamel.nl/codecs/Est_(superscript)">
          <glyphName>est symbol</glyphName>
          <note>A symbol resembling the Arabic number 2 and standing for Latin "est" or Gaelic
            orthographic equivalents. May be in-line with the text or superscript.</note>
        </glyph>
        <glyph id="g19" corresp="https://www.vanhamel.nl/codecs/Uir_(subscript_ir)">
          <glyphName>uir (subscript ir)</glyphName>
          <note>A "u" with a descender on the right-hand side with cross-stroke and a superscript
            "i"; stands for "uir".</note>
        </glyph>
        <!-- EPT to check instances of g19 are all the symbol described here and not u + g46. -->
        <glyph id="g20" corresp="https://www.vanhamel.nl/codecs/E_(superscript)">
          <glyphName>superscript e</glyphName>
          <note>A superscript "e", usually standing for standing for "er" or "re".</note>
        </glyph>
        <glyph id="g21" corresp="https://www.vanhamel.nl/codecs/Er_(vertical_tilde)">
          <glyphName>vertical tilde</glyphName>
          <note>A vertical tilde, most often standing for "e(a)r" but may also represent
            "(e)ir".</note>
        </glyph>
        <glyph id="g22" corresp="https://www.vanhamel.nl/codecs/Err_(inverted_tildes)">
          <glyphName>double vertical tilde</glyphName>
          <note>Two vertical tildes, most often standing for "e(a)rr" but may also represent
            "(e)irr".</note>
        </glyph>
        <glyph id="g23" corresp="https://www.vanhamel.nl/codecs/Suspension_stroke_(double)">
          <glyphName>double suspension stroke</glyphName>
          <note>Two superscript or mid-level horizontal strokes, generally indicating that the
            expansion contains a double consonant.</note>
        </glyph>
        <glyph id="g24" corresp="https://www.vanhamel.nl/codecs/D_(superscript)">
          <glyphName>superscript d</glyphName>
          <note>A superscript "d", standing for a "d" with preceding or succeeding letter(s).</note>
        </glyph>
        <glyph id="g25" corresp="https://vanhamel.nl/codecs/I_(superscript)">
          <glyphName>superscript i</glyphName>
          <note>A superscript "i", usually standing for "ri" or "ir".</note>
        </glyph>
        <glyph id="g26" corresp="https://www.vanhamel.nl/codecs/2_(Arabic_numeral)">
          <glyphName>Arabic numeral 2</glyphName>
          <note>An Arabic numeral "2", in-line or superscript, usually standing for "dá" or "da" in
            Gaelic contexts and "duo" in Latin contexts.</note>
        </glyph>
        <glyph id="g27">
          <glyphName>[none]</glyphName>
          <note>There is no visual indicator of an abbreviation but it is apparent for some reason
            that a word has been abbreviated, e.g. a single letter standing for a frequently-used
            name.</note>
        </glyph>
        <glyph id="g28" corresp="https://www.vanhamel.nl/codecs/Con_(reversed_c)">
          <glyphName>con symbol</glyphName>
          <note>A reversed "c", standing for "co(n)", or a word beginning with "con".</note>
        </glyph>
        <glyph id="g29" corresp="https://www.vanhamel.nl/codecs/T_(superscript)">
          <glyphName>superscript t</glyphName>
          <note>A superscript "t", sanding for a "t" with preceding or succeeding letter(s).
            Sometimes modified with "i" and/or lenition.</note>
        </glyph>
        <glyph id="g30" corresp="https://www.vanhamel.nl/codecs/Q_(regular)">
          <glyphName>letter q</glyphName>
          <note>Latin letter "q", standing for "cu".</note>
        </glyph>
        <glyph id="g31" corresp="https://www.vanhamel.nl/codecs/7_(Arabic_numeral)">
          <glyphName>Arabic numeral 7</glyphName>
          <note>The Arabic numeral 7, standing for "se(a)ch(t/d)" or similar.</note>
        </glyph>
        <glyph id="g32" corresp="https://www.vanhamel.nl/codecs/Et_reliqua">
          <glyphName>et reliqua</glyphName>
          <note>Ligatured tironian et and rl with cross stroke, standing for either "et reliqua" or
            "ocus araile" and orthographic variants.</note>
        </glyph>
        <glyph id="g33" corresp="https://www.vanhamel.nl/codecs/M_(vertical)">
          <glyphName>vertical m</glyphName>
          <note>A descending character, looped on the right-hand side, standing for "m". This is
            usually attached to the previous letter and generally has three (or more) loops.</note>
        </glyph>
        <glyph id="g34" corresp="https://www.vanhamel.nl/codecs/Pro_(p_with_tail)">
          <glyphName>pro symbol</glyphName>
          <note>Letter "p" with a back-pointing tail on the descender standing for "pro".</note>
        </glyph>
        <glyph id="g35">
          <glyphName>Arabic numeral 9</glyphName>
          <note>An Arabic numeral "9", usually standing for "n" with succeding vowel(s), e.g. "na",
            "noi", "naoi".</note>
        </glyph>
        <glyph id="g36" corresp="https://www.vanhamel.nl/codecs/Per_(p_with_cross_stroke)">
          <glyphName>p with cross-stroke</glyphName>
          <note>A "p" with a cross stroke on the descender, usually standing for "per", "par" or
            "por".</note>
        </glyph>
        <glyph id="g37">
          <glyphName>reversed Arabic numeral 9</glyphName>
          <note>The Arabic numeral 9, flipped vertically, standing for "an".</note>
        </glyph>
        <glyph id="g38">
          <glyphName>superscript io</glyphName>
          <note>Superscript "io", usually standing for "ior".</note>
        </glyph>
        <glyph id="g39" corresp="https://www.vanhamel.nl/codecs/Eile_(ee)">
          <glyphName>ee or ėė</glyphName>
          <note>Double e, which may appear with supersript puncta, standing for "eile". May also be
            accompanied by enclosing puncta.</note>
        </glyph>
        <!-- MM to check if "May also be accompanied by enclosing puncta." is accurate. -->
        <glyph id="g40">
          <glyphName>superscript b</glyphName>
          <note>A superscript b, standing for a "b" with preceding or succeeding letter(s).</note>
        </glyph>
        <glyph id="g41" corresp="https://www.vanhamel.nl/codecs/L_with_suspension_stroke">
          <glyphName>ll with bisecting cross-stroke</glyphName>
          <note>Double "l" with bisecting mid-level cross stroke, generally, but not always,
            standing for a multi-syllabic abbreviation.</note>
        </glyph>
        <glyph id="g42" corresp="https://www.vanhamel.nl/codecs/S_(superscript)">
          <glyphName>superscript s</glyphName>
          <note>A superscript "s", often a majuscule "s" without the lower leftward loop, standing
            for an "s" with preceding or succeeding letter(s).</note>
        </glyph>
        <glyph id="g43" corresp="https://www.vanhamel.nl/codecs/I_(subscript)">
          <glyphName>subscript i</glyphName>
          <note>A subscript "i", standing for an "i" with preceding or suceeding letter(s).</note>
        </glyph>
        <glyph id="g44" corresp="https://www.vanhamel.nl/codecs/S_insular_with_cross_stroke">
          <glyphName>s with cross-stroke</glyphName>
          <note>A long insular "s" with a cross-stroke, standing for "s", with succeeding
            letter(s).</note>
        </glyph>
        <glyph id="g45" corresp="https://www.vanhamel.nl/codecs/Vel_(l_with_stroke)">
          <glyphName>l (L) with suspension stroke (vel)</glyphName>
          <note>An "l" with a suspension stroke, standing for Latin "vel" or Gaelic "no" and
            orthographic variants.</note>
        </glyph>
        <glyph id="g46" corresp="https://www.vanhamel.nl/codecs/Ir_(cross_stroke)">
          <glyphName>ir abbreviation</glyphName>
          <note>An "i" with a cross-stroke, often set below the inner writing tracks, standing for
            "ir".</note>
        </glyph>
        <glyph id="g47">
          <glyphName>superscript ll (LL) with bisecting cross-stroke</glyphName>
          <note>Superscript double "l" with bisecting mid-level cross stroke, generally, but not
            always, standing for a multi-syllabic abbreviation.</note>
        </glyph>
        <glyph id="g48">
          <glyphName>curly descender with cross-stroke</glyphName>
          <note>A curly, left-facing descender (not unlike a us symbol) with a cross-stroke,
            standing for "-uir", or perhaps "-air".</note>
        </glyph>
        <glyph id="g49" corresp="https://www.vanhamel.nl/codecs/Cath_(.k.)">
          <glyphName>capital K</glyphName>
          <note>A Roman "k", mostly standing for "cath", although can also stand for "kalends". May
            be accompanied by enclosing puncta.</note>
          <!-- MM to check "May be accompanied by enclosing puncta." is accurate. -->
        </glyph>
        <glyph id="g50" corresp="https://www.vanhamel.nl/codecs/Apostrophe_(superscript)">
          <glyphName>apostrophe</glyphName>
          <note>A left-facing superscript semi-circle, standing for "-s", perhaps with a preceding
            letter(s).</note>
        </glyph>
        <glyph id="g51" corresp="https://www.vanhamel.nl/codecs/X_(regular)">
          <glyphName>letter x</glyphName>
          <note>An "x", standing either for the numeral 10, for the spelling "deich", "deith", or
            similar.</note>
        </glyph>
        <glyph id="g52"
          corresp="https://www.vanhamel.nl/codecs/I_with_acute_diacritic_(disambiguation)">
          <glyphName>upward diagonal overstroke</glyphName>
          <note>A stroke from the lower left of a character to the upper right, crossing the
            character itself, standing for a string of subsequent letters.</note>
        </glyph>
        <glyph id="g53">
          <glyphName>double character</glyphName>
          <note>The same character repeated, standing for that character capitalised.</note>
        </glyph>
        <glyph id="g54">
          <glyphName>colon</glyphName>
          <note>Two puncta, one above the other, standing for any character(s).</note>
        </glyph>
        <glyph id="g55">
          <glyphName>Arabic numeral 2 with stroke</glyphName>
          <note>An Arabic numeral "2" with a diagonal stroke across, standing for for "dai".</note>
        </glyph>
        <glyph id="g56">
          <glyphName>double c</glyphName>
          <note>Letters "cc", standing for an abbreviation for a word starting with "cc".</note>
        </glyph>
        <glyph id="g57">
          <glyphName>superscript l (L)</glyphName>
          <note>A superscript "l", standing for an "l" with preceding or succeeding
            letter(s).</note>
        </glyph>
        <glyph id="g58">
          <glyphName>superscript il</glyphName>
          <note>Superscript "il", standing for a "il" with preceding or succeeding letter(s).</note>
        </glyph>
        <glyph id="g59">
          <glyphName>superscript r</glyphName>
          <note>A superscript "r", standing for a "r" with preceding or succeeding letter(s).</note>
        </glyph>
        <glyph id="g60" corresp="https://www.vanhamel.nl/codecs/Quod_(q_with_curved_stroke)">
          <glyphName>letter q with curved stroke</glyphName>
          <note>Letter "q" with an additional stroke originating at the right-hand side of the
            triangular body of the "q". The stroke extends a little to the right before curving
            downwards to the left and across the descender. This stands for "cu" in Gaelic contexts
            and "quod" in Latin contexts.</note>
        </glyph>
        <glyph id="g61">
          <glyphName>um/am abbreviation</glyphName>
          <note>A Roman "z" with a diagonal cross-stroke, standing stands an "m" with preceding
            vowel in Latin genitive plurals.</note>
        </glyph>
        <glyph id="g62">
          <glyphName>capital R</glyphName>
          <note>A Roman capital "R" standing for "cath". Appears to be an alternative but distinct
            form of Roman "K" abbreviation for cath.</note>
        </glyph>
        <glyph id="g63">
          <glyphName>punctum</glyphName>
          <note>A single punctum placed after a letter, usually standing for the rest of a
            well-known or frequently-used word or name.</note>
        </glyph>
        <glyph id="g64">
          <glyphName>allmara-/allmari- abbreviation</glyphName>
          <note>Ligature form of the letter "a" with superscript suspension stroke which is
            connected to the body of the letter at the top on the right-hand side, standing for the
            "allmara" or "allmari" portion of the word "allmarach".</note>
        </glyph>
        <glyph id="g65" corresp="https://www.vanhamel.nl/codecs/3_(Arabic_numeral)">
          <glyphName>Arabic numeral 3</glyphName>
          <note>A symbol resembling the Arabic numeral "3" found in one text in this corpus where it
            is accompanied by a following "i" and where it represents the "guins" portion of
            "guinsi" a variant orthographic form of "a dh'ionnsaigh".</note>
        </glyph>
        <glyph id="g66">
          <glyphName>oo, ȯȯ or óó</glyphName>
          <note>Double "o", which may be accompanied by two superscript puncta or acute strokes,
            standing for "eile" or variants.</note>
        </glyph>
        <glyph id="g67">
          <glyphName>urr symbol</glyphName>
          <note>Letter u with a descender on the right-hand side and two cross strokes standing for
            "urr".</note>
        </glyph>
        <glyph id="g68">
          <glyphName>double Tironian Nota with suspenion strokes</glyphName>
          <note>Two Tironian Notas, shortened at both ends, with two short superscript suspension
            strokes standing for "eile" or variants.</note>
        </glyph>
        <glyph id="g69">
          <glyphName>uu or .uu. or uu. or .u.u.</glyphName>
          <note>Double "u" which may accompanied by a single punctum or enclosing puncta, standing
            for "uile".</note>
        </glyph>
        <glyph id="g70" corresp="https://www.vanhamel.nl/codecs/U%C3%AD_(.h.)">
          <glyphName>h abbreviation</glyphName>
          <note>An "h", which may accompanied by enclosing puncta, standing for "úa" or an inflected
            form thereof.</note>
        </glyph>
        <glyph id="g71">
          <glyphName>ŋ abbreviation</glyphName>
          <note>The character "ŋ", standing for "na".</note>
        </glyph>
        <glyph id="g72">
          <glyphName>r with cross-stroke</glyphName>
          <note>An "r" with a cross-stroke on the descender, standing for "rr".</note>
        </glyph>
        <glyph id="g73">
          <glyphName>i with two cross-strokes</glyphName>
          <note>An "i", which may extend below the line, with two cross-strokes, standing for for
            "irr".</note>
        </glyph>
        <glyph id="g74">
          <glyphName>vertical n</glyphName>
          <note>A descending character, looped on the right-hand side, standing for "n". This is
            usually attached to the previous letter and generally has two loops.</note>
        </glyph>
        <glyph id="g75">
          <glyphName>íí</glyphName>
          <note>Double "i" with a thin acute plume on each, standing for "eile" or orthographic
            equivalents.</note>
        </glyph>
        <glyph id="g76">
          <glyphName>y within puncta</glyphName>
          <note>A "y" placed within enclosing puncta (.y.).</note>
        </glyph>
        <glyph id="g77">
          <glyphName>airr symbol</glyphName>
          <note>An "a" with a descender on the right-hand side (very like "q"), with two
            cross-strokes on the descender, and superscript "i", standing for "airr".</note>
        </glyph>
        <glyph id="g78">
          <glyphName>Tironian Nota with suspension stroke as a single abbreviation</glyphName>
          <note>A Tironian Nota with a suspension stroke which, combined, stand for "enn" or
            "end".</note>
        </glyph>
        <glyph id="g79">
          <glyphName>superscript g</glyphName>
          <note>A superscript "g", standing for a "g" with preceding or succeeding letter(s).</note>
        </glyph>
        <glyph id="g80">
          <glyphName>Arabic numeral 6</glyphName>
          <note>The Arabic numeral 6, standing for "sé", "se", "sia", "sex" or variants
            thereof.</note>
        </glyph>
        <glyph id="g81">
          <glyphName>superscript e on i</glyphName>
          <note>A superscript "e" over an "i", standing for "eirigh".</note>
        </glyph>
        <glyph id="g82" corresp="https://www.vanhamel.nl/codecs/Que_(digraph)">
          <glyphName>que symbol</glyphName>
          <note>A digraph of "q" and the us symbol (which looks somewhat like a semi-colon, ;), but
            standing for Latin "que".</note>
        </glyph>
        <glyph id="g83">
          <glyphName>superscript m</glyphName>
          <note>A superscript "m", standing for an "m" with preceding or succeeding
            letter(s).</note>
        </glyph>
        <glyph id="g84">
          <glyphName>Roman numeral ii</glyphName>
          <note>The Roman numeral "ii", standing for "da".</note>
        </glyph>
        <glyph id="g85">
          <glyphName>Tironian Nota with double suspension stroke</glyphName>
          <note>A Tironian Nota with double suspension stroke which, combined, stand for "enn" or
            "end".</note>
        </glyph>
        <glyph id="g86">
          <glyphName>superscript n</glyphName>
          <note>A superscript "n", standing for an "n" with preceding or succeeding
            letter(s).</note>
        </glyph>
        <glyph id="g87">
          <glyphName>cros abbreviation</glyphName>
          <note>A hieroglyphic "+", standing for "cros" and similar.</note>
        </glyph>
        <glyph id="g88">
          <glyphName>supercipt ic</glyphName>
          <note>Superscript "ic", usually over "m" and standing for "hic" in "mhic".</note>
        </glyph>
        <glyph id="g89">
          <glyphName>leiges symbol</glyphName>
          <note>An "l", suspension stroke, and inverted "s", standing for "leiges".</note>
        </glyph>
        <glyph id="g90">
          <glyphName>ćć or ċċ or čč</glyphName>
          <note>Double "c" with accute or grave plumes, two puncta or two carons, standing for
            c(h)éile.</note>
        </glyph>
        <glyph id="g91">
          <glyphName>double superscript u/v</glyphName>
          <note>The letter "u/v" written twice in superscript, standing for "urr".</note>
        </glyph>
        <glyph id="g92">
          <glyphName>Éire(nn) symbol</glyphName>
          <note>Letter "o" with superscript "e", which may have a punctum above the "o", standing
            for a form of Éire, usually inflected.</note>
        </glyph>
        <glyph id="g93" corresp="https://www.vanhamel.nl/codecs/Are_(ligature_of_ar_and_e)">
          <glyphName>ar symbol with ligatured e</glyphName>
          <note>Letter "a" with a descender on the right-hand side (very like "q") with a
            cross-stroke on the descender and a ligatured "e", standing for "are".</note>
        </glyph>
        <glyph id="g94">
          <glyphName>air symbol with ligatured e</glyphName>
          <note>Letter "a" with a descender on the right-hand side (very like "q") with a
            cross-stroke on the descender, superscript "i", and a ligatured "e", standing for"
            aire".</note>
        </glyph>
        <glyph id="g95">
          <glyphName>o with superscript diagonal arch</glyphName>
          <note>The letter "o" with an diagonal arch, beginning at the top of the letter on the
            left-hand side and extending upwards and to the right, standing for "agus".</note>
        </glyph>
        <glyph id="g96">
          <glyphName>Arabic numeral 10</glyphName>
          <note>An Arabic numeral "10", standing for "de(i)ch", "de(i)th", or similar.</note>
        </glyph>
        <glyph id="g97" corresp="https://www.vanhamel.nl/codecs/Id_est_(.i.)">
          <glyphName>.i. abbreviation</glyphName>
          <note>Letter "i" with enclosing puncta, when used to supply characters of another word,
            standing for "edon" or similar.</note>
        </glyph>
        <glyph id="g98">
          <glyphName>qui symbol</glyphName>
          <note>A "q" with an ascender as well as a descender, resembling a backwards "þ", standing
            for "qui" in Latin contexts.</note>
        </glyph>
        <glyph id="g99">
          <glyphName>superscript ll</glyphName>
          <note>Double superscript "l", standing for a "ll" with preceding or succeeding
            letter(s).</note>
        </glyph>
        <glyph id="g100" corresp="https://www.vanhamel.nl/codecs/4_(Arabic_numeral)">
          <glyphName>Arabic numeral 4</glyphName>
          <note>An Arabic numeral "4", standing for "cethair", "cethra", or similar.</note>
        </glyph>
        <glyph id="g101">
          <glyphName>u with descender and cross-stroke</glyphName>
          <note>A "u" with a descender on the right-hand side and cross-stroke, standing for
            "ur".</note>
        </glyph>
        <glyph id="g102">
          <glyphName>Arabic numeral 8</glyphName>
          <note>An Arabic numeral "8", standing for "ocht" or similar.</note>
        </glyph>
        <glyph id="g103">
          <glyphName>Superscript oi</glyphName>
          <note>"oi" in superscript, standing for "oir".</note>
        </glyph>
        <glyph id="g104">
          <glyphName>Superscript ia</glyphName>
          <note>"ia" in superscript, usually in a Latin word, standing for the ending "-ia" plus one
            or more preceding characters.</note>
        </glyph>
        <glyph id="g105">
          <glyphName>dp abbreviation</glyphName>
          <note>"ia" in superscript, usually in a Latin word, standing for the ending "-ia" plus one
            or more preceding characters.</note>
        </glyph>
        <glyph id="g106">
          <glyphName>bisected c</glyphName>
          <note>"c" bisected with a short, downward pointing cross stroke.</note>
        </glyph>
        <glyph id="g107" corresp="https://www.vanhamel.nl/codecs/Are_(ligature_of_ar_and_e)">
          <glyphName>aer abbreviation</glyphName>
          <note>An "ae" digraph, with a crossed descender on the "a" (i.e. "ar"), standing for "aer"
            and possibly also "are".</note>
        </glyph>
        <glyph id="l1">
          <glyphName>ligature</glyphName>
          <note>Two letters are placed together as a ligature; this is a generic category for
            ligatures for which a separate glyph has not been created.</note>
        </glyph>
        <glyph id="l2" corresp="https://www.vanhamel.nl/codecs/He_(ligature)">
          <glyphName>He ligature</glyphName>
          <note>An "h" with an "e" atop its ascender.</note>
        </glyph>
        <glyph id="l3" corresp="https://www.vanhamel.nl/codecs/Ac_(ligature)">
          <glyphName>ac ligature</glyphName>
          <note>Various combinations of "a" and "c".</note>
        </glyph>
        <glyph id="l4" corresp="https://vanhamel.nl/codecs/Re_(ligature_with_short_e)">
          <glyphName>re ligature</glyphName>
          <note>"r" run into "e", the former perhaps resembling "s".</note>
        </glyph>
        <glyph id="l5" corresp="https://vanhamel.nl/codecs/Ao_(ligature)">
          <glyphName>ao ligature</glyphName>
          <note>"a" without a backstroke, followed by "o".</note>
        </glyph>
        <glyph id="l6" corresp="https://vanhamel.nl/codecs/Rr_(ligature)">
          <glyphName>rr ligature</glyphName>
          <note>"rr", with the first "r" reduced to a single descender and its hook merged with the
            top of the descender of the second "r".</note>
        </glyph>
        <glyph id="l7" corresp="https://www.vanhamel.nl/codecs/Et_(digraph)">
          <glyphName>et digraph</glyphName>
          <note>"et", with the middle stroke of "e" also the cross-stroke of "t" and the "e"
            enclosing the "t".</note>
        </glyph>
        <glyph id="l8">
          <glyphName>am ligature</glyphName>
          <note>"a" without a backstroke, followed by "m".</note>
        </glyph>
        <glyph id="l9" corresp="https://www.vanhamel.nl/codecs/Ae_(digraph)">
          <glyphName>ae ligature</glyphName>
          <note>"a" without a backstroke, followed by "e".</note>
        </glyph>
        <glyph id="l10">
          <glyphName>ag ligature</glyphName>
          <note>"a" without a backstroke, followed by "g".</note>
        </glyph>
        <glyph id="l12">
          <glyphName>ad ligature</glyphName>
          <note>"a" without a backstroke, followed by "d".</note>
        </glyph>
        <glyph id="l13">
          <glyphName>ai ligature</glyphName>
          <note>"a" without a backstroke, followed by "i".</note>
        </glyph>
        <glyph id="l14">
          <glyphName>at ligature</glyphName>
          <note>"a" without a backstroke, followed by "t".</note>
        </glyph>
        <glyph id="l15" corresp="https://www.vanhamel.nl/codecs/Na_(digraph)">
          <glyphName>na digraph</glyphName>
          <note>"n", with "a" dangling beneath the line, attached to the second minim of "n".</note>
        </glyph>
        <glyph id="l16" corresp="https://www.vanhamel.nl/codecs/Ea_(ligature_allograph)">
          <glyphName>ea ligature</glyphName>
          <note>An "e" with an "a" dangling below, standing for "ea".</note>
        </glyph>
        <glyph id="l17" corresp="https://www.vanhamel.nl/codecs/Di_(ligature)">
          <glyphName>di ligature</glyphName>
          <note>A "d" with a long back-stroke, standing for "di".</note>
        </glyph>
        <glyph id="l18">
          <glyphName>er digraph</glyphName>
          <note>A "e" followed by an "r", whose serif replaces the middle cross-stroke of the
            "e"..</note>
        </glyph>
        <glyph id="l19">
          <glyphName>et and p ligature</glyphName>
          <note>A tironian "et" whose backstroke is also the descender of the following "p".</note>
        </glyph>
        <glyph id="l20">
          <glyphName>as ligature</glyphName>
          <note>"a" without a backstroke, followed by "s".</note>
        </glyph>
        <glyph id="l21">
          <glyphName>et and r ligature</glyphName>
          <note>A tironian "et" whose backstroke is also the descender of the following "r".</note>
        </glyph>
        <glyph id="l22">
          <glyphName>pp ligature</glyphName>
          <note>Double "p": the loop of the first "p" is merged with the backstroke of the second
            "p".</note>
        </glyph>
        <glyph id="l23">
          <glyphName>ss ligature</glyphName>
          <note>Double "s" (minuscule): the first "s" is a simple descender, with the short loop
            merged with the serif on the second "s".</note>
        </glyph>
        <glyph id="l24">
          <glyphName>et and f ligature</glyphName>
          <note>A tironian "et" whose backstroke is also the descender of the following "f".</note>
        </glyph>
        <glyph id="l25">
          <glyphName>do ligature</glyphName>
          <note>A "d" whose back-stroke forms part of a following "o".</note>
        </glyph>
        <glyph id="l26">
          <glyphName>a and n</glyphName>
          <note>An "a" whose back-stoke forms one of the minims of a following "n".</note>
        </glyph>
        <glyph id="l27">
          <glyphName>a and u</glyphName>
          <note>An "a" whose back-stoke forms one of the minims of a following "u".</note>
        </glyph>
        <glyph id="l28">
          <glyphName>u and d</glyphName>
          <note>A "u" whose second minim is part of the loop of a following "d".</note>
        </glyph>
        <glyph id="l29">
          <glyphName>a, e, and n ligature</glyphName>
          <note>A ligatured "a", although taller than usuual, whose back-stoke forms one of the
            minims of a following "n", standing for "aen".</note>
        </glyph>
        <glyph id="l30">
          <glyphName>sp ligature</glyphName>
          <note>A reversed "s", sharing a backstroke with a following "p".</note>
        </glyph>
        <glyph id="l31">
          <glyphName>b + f ligature</glyphName>
          <note/>
        </glyph>
        <glyph id="l32">
          <glyphName>le ligature</glyphName>
          <note/>
        </glyph>
        <glyph id="l33">
          <glyphName>tt ligature</glyphName>
          <note/>
        </glyph>
        <glyph id="l34">
          <glyphName>ta ligature</glyphName>
          <note/>
        </glyph>
        <glyph id="l35">
          <glyphName>ni ligature</glyphName>
          <note/>
        </glyph>
        <glyph id="l36">
          <glyphName>ar ligature</glyphName>
          <note/>
        </glyph>
        <glyph id="l37" corresp="https://www.vanhamel.nl/codecs/IS_(vertical_S_through_I)">
          <glyphName>is ligature</glyphName>
          <note/>
        </glyph>
        <glyph id="l38" corresp="https://www.vanhamel.nl/codecs/De_(ligature)">
          <glyphName>de ligature</glyphName>
          <note/>
        </glyph>
        <glyph id="l41">
          <glyphName>la ligature</glyphName>
          <note>An "l" with a "z" attached in subscript, standing for "la".</note>
        </glyph>
        <glyph id="l42">
          <glyphName>ca ligature</glyphName>
          <note>An "c" with a downward flick at the lower end of the loop, standing for "ca".</note>
        </glyph>
        <glyph id="l43">
          <glyphName>ab ligature</glyphName>
          <note>An "a" whose back-stoke forms one of the minims of a following "b".</note>
        </glyph>
        <glyph id="l44">
          <glyphName>ua ligature</glyphName>
          <note>An "u" whose second stoke forms part of the loop of the following "a".</note>
        </glyph>
        <glyph id="l45">
          <glyphName>oe ligature</glyphName>
          <note>An "o" that also forms the back of the following "a".</note>
        </glyph>
        <glyph id="l46">
          <glyphName>ale ligature</glyphName>
          <note>An "a" whose back-stoke is formed by the following "b".</note>
        </glyph>
        <glyph id="l47">
          <glyphName>ir digraph</glyphName>
          <note>A tall loop, like the "e" component of "&amp;", apparently standing for "ir"
            ("senoirig").</note>
        </glyph>
        <glyph id="l48">
          <glyphName>cc ligature</glyphName>
          <note>The end-points of the first "c" are joined to the back of the next "c".</note>
        </glyph>
        <glyph id="l49">
          <glyphName>ru ligature</glyphName>
          <note>The left side of the "r" is combined with the right side of the right hand descender
            of the "u" so it almost looks like "ri", but the two letters are connected by a stroke
            at line level.</note>
        </glyph>
        <glyph id="l50">
          <glyphName>ma digraph</glyphName>
          <note>The "a" is below the line and formed from a descender running off from the final
            minim of the "m".</note>
        </glyph>
        <glyph id="l51">
          <glyphName>cr ligature</glyphName>
          <note>A "c" ligatured with the following "r".</note>
        </glyph>
        <glyph id="l52">
          <glyphName>air + e digraph</glyphName>
          <note>The standard abbreviation for "air", ligatured with a small "e" (like "ar symbol
            with ligatured e" but with a superscript "i").</note>
        </glyph>
        <glyph id="l53">
          <glyphName>pe ligature</glyphName>
          <note>A "p" whose loop forms the back of the curve on the following "e".</note>
        </glyph>
        <glyph id="l54">
          <glyphName>ia digraph</glyphName>
          <note>An "i" with an open "a" below, with one end of the "a" attached to the "i"
            minim.</note>
        </glyph>
        <glyph id="l55">
          <glyphName>bo ligature</glyphName>
          <note>A "b" whose loop forms part of the following "o".</note>
        </glyph>
        <glyph id="l56">
          <glyphName>et + e digraph</glyphName>
          <note>A tironian et which also forms the back of the loop of a following "e".</note>
        </glyph>
        <glyph id="l57">
          <glyphName>ha ligature</glyphName>
          <note>An "h" with an open "a" in subscript attached to the second downward stroke of the
            "h".</note>
        </glyph>
        <glyph id="l58">
          <glyphName>da ligature</glyphName>
          <note>A "d" with an "a" placed in subscript below it, possibly open and connected to the
            "d".</note>
        </glyph>
        <glyph id="l59">
          <glyphName>ui ligature</glyphName>
          <note>A "u" with a descender, standing for "ui".</note>
        </glyph>
        <glyph id="l60">
          <glyphName>sr ligature</glyphName>
          <note>An "s" and and "r" sharing the same descender.</note>
        </glyph>
        <glyph id="l61">
          <glyphName>a + r ligature</glyphName>
          <note>A standard "ar" abbreviation whose descender is also the descender of a following
            "r".</note>
        </glyph>
			</xml>
XML;
	private $_element;  //the element with the ID passed to the constructor

	public function __construct($id) {
		$xml = new \SimpleXMLElement($this->_glygatures);
		$results = $xml->xpath("/xml/glyph[@id='{$id}']");
		$this->_element = $results[0];
	}

	public function getName() {
		return $this->_element->glyphName;
	}

	public function getNote() {
		return $this->_element->note;
	}

	public function getCorresp() {
		return $this->_element->attributes()->corresp;
	}
}