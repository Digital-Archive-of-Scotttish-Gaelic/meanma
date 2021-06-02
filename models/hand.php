<?php


namespace models;


class hand
{
	private $_handNotes = <<<XML
		<handNotes>
        <handNote xml:id="Hand1">
          <date from="1100" to="1150" min="1100" max="1200">12</date>
          <region cert="high">NE Scotland</region>
          <affiliation>deer</affiliation>
          <note><p>Hand A in the Gaelic Notes in the Book of Deer</p>
            <p>Jackson (1972): (p.12) Writing commences small, then varies in size. (p.13) It could
              date from the early to mid-12th century and must date from after 1038 at the earliest,
              as this is when Máel Snechta, whose grant he records, became mormaer of Moray. (p.16)
              Contemporary with B; late 11th/early 12th centuries?</p>
          </note>
        </handNote>
        <handNote xml:id="Hand2">
          <date from="1100" to="1150" min="1100" max="1200">12</date>
          <region cert="high">NE Scotland</region>
          <affiliation>deer</affiliation>
          <note><p>Hand B in the Gaelic Notes in the Book of Deer</p><p>Jackson (1972): (p.13)
              Similar to A; makes much use of margins to keep his text together. (p.16) Contemporary
              with A; late 11th/early 12th centuries?</p></note>
        </handNote>
        <handNote xml:id="Hand3">
          <date from="1100" to="1150" min="1100" max="1200">12</date>
          <region cert="high">NE Scotland</region>
          <affiliation>deer</affiliation>
          <note><p>Hand C in the Gaelic Notes in the Book of Deer</p><p>Jackson (1972): (p.13) Uses
              paler and browner ink. Writing is spidery. More use of lenition marks. (p.15) Later
              than A and B. (p.16) "lateish in the period" [what is the period?]</p></note>
        </handNote>
        <handNote xml:id="Hand4">
          <date from="1100" to="1150" min="1100" max="1200">12</date>
          <region cert="high">NE Scotland</region>
          <affiliation>deer</affiliation>
          <note><p>Hand D in the Gaelic Notes in the Book of Deer</p><p>Jackson (1972): (p.14) Less
              formal. More use of m-suspension and abbreviations for <hi rend="italics">mac</hi> and
                <hi rend="italics">meic</hi>. Use of continental "g" (<hi rend="italics">fer
                leiginn</hi>). (p.15) Later than A and C [and therefore B?]. (p.16) No earlier than
              1131.</p></note>
        </handNote>
        <handNote xml:id="Hand5">
          <date from="1100" to="1150" min="1100" max="1200">12</date>
          <region cert="high">NE Scotland</region>
          <affiliation>deer</affiliation>
          <note><p>Hand E in the Gaelic Notes in the Book of Deer</p><p>Jackson (1972): (p.15):
              Wrote the Charter of David I. (p.16) A continental hand, with Hiberno-Saxon
              influences. (p.16) Late in the period.</p></note>
        </handNote>
        <handNote xml:id="Hand6">
          <date cert="medium" from="0900" to="1000" min="900" max="1000">10</date>
          <region cert="high">NE Scotland</region>
          <affiliation>deer</affiliation>
          <note>Scribe of main text in the Book of Deer</note>
        </handNote>
        <handNote xml:id="Hand7">
          <surname cert="medium">MacEwen (?)</surname>
          <date from="1450" to="1550" min="1501" max="1599">15/16</date>
          <region cert="medium">Argyll</region>
          <affiliation>mcewen</affiliation>
          <note><p>This is the scribe of '<ref target="T2">Aingil Dé dom dhín</ref>' in <ref
                target="MS2">London, BL MS. Egerton 2899</ref> and a set of Columban poems in
              Edinburgh, NLS Adv. MS 72.1.31. Black has suggested that he was a MacEwen working for
              the Campbells of Glenorchy.</p>
            <p>On the whole, the scribe follows the conventions of medieval Gaelic orthography and
              restricts himself to the most conventional abbreviations.</p>
            <p>The scribe displays a curious attitude towards lenition, often omitting it from
              monosyllables ending with /X'/ (e.g. <hi rend="italic">nac</hi>, <ref
                target="#T2.1.1d">q1d</ref> not <hi rend="italic">nach</hi>. At the same time,
              lenition markers are added where lenition would not normally be written (e.g. <hi
                rend="italic">mainḋir</hi>, <ref target="#T2.1.2b">q2b</ref>. There is also one
              occasion where lenition is used to indicate eclipsis (<ref target="#T2.1.10a"
                >q10a</ref>). The absence of lenition from final /X'/ may be designed to represent
              Scottish Gaelic pronounciation in some way, but I am yet to find a way of making sense
              of the feature along these lines.</p>
            <p>He also tends to add a hair-stroke flick towards the upper right above the letter
              “i”, in the absence of any other diacritic. This closely resembles an acute accent but
              is probably meant merely as a minim marker. Examination of words in which “i” really
              should have an acute accent (e.g. <ref target="#T2-1-17b">q17b</ref>, “nac bí”; <ref
                target="#T2-1-24c">q24c</ref>, “tísat”) reveals no reliable visual indicator of when
              the flick is an accent or not, so accents on “i” are included in the diplomatic text
              simply when they would be expected in mainstream Classical Gaelic orthography.</p>
          </note>
        </handNote>
        <handNote xml:id="Hand8">
          <forename>Eoghan</forename>
          <surname>Mac Pháil</surname>
          <date cert="high" from="1600" to="1650" min="1600" max="1700">17</date>
          <region cert="high">W Scotland</region>
          <affiliation>emp</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand9">
          <forename>Eoghan</forename>
          <surname>Mac Gilleoin</surname>
          <date cert="medium" from="1650" to="1700" min="1600" max="1700">17</date>
          <region cert="high">SW Scotland</region>
          <affiliation>misc</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand10">
          <forename>Niall</forename>
          <surname>MacMhuirich</surname>
          <date cert="medium" from="1600" to="1660" min="1601" max="1799">17/18</date>
          <region cert="high">W Scotland</region>
          <affiliation>macmhuirich</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand11">
          <forename>Domnall</forename>
          <surname>MacMhuirich</surname>
          <date type="floruit" cert="medium" from="1707" to="1745" min="1700" max="1800">18</date>
          <region cert="high">W Scotland</region>
          <affiliation>macmhuirich</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand12">
          <date type="floruit" cert="medium" from="1500" to="1590" min="1500" max="1600">16</date>
          <region cert="high">W Scotland</region>
          <affiliation>beaton</affiliation>
          <note>Hand M in NLS Adv. MS 72.1.33.</note>
        </handNote>
        <handNote xml:id="Hand13">
          <forename>Lachlan Mór</forename>
          <surname>MacLean</surname>
          <date type="floruit" cert="medium" from="1577" to="1598" min="1500" max="1600">16</date>
          <region cert="high">Isle of Mull</region>
          <affiliation>misc</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand14">
          <forename>James</forename>
          <surname>Beaton</surname>
          <date type="floruit" cert="medium" from="1613" to="1621" min="1600" max="1700">17</date>
          <region/>
          <affiliation>beaton</affiliation>
          <note>Black's Hand K (NLS Adv. 72.1.33)</note>
        </handNote>
        <handNote xml:id="Hand15">
          <date type="floruit" cert="low" from="1590" to="1700" min="1600" max="1700">17</date>
          <region/>
          <affiliation>beaton</affiliation>
          <note>Like James Beaton's, this hand makes occasional brief interventions in Transcription
            5.2 (NLS Adv. MS. 72.1.33). It is mucher neater than James Beaton's and the main
            scribes, with each stroke an angular block strongly distinguished from the
            others.</note>
        </handNote>
        <handNote xml:id="Hand16">
          <forename>John</forename>
          <surname>Beaton</surname>
          <date type="life" cert="medium" from="1640" to="1715" min="1640" max="1715">17</date>
          <region>Mull</region>
          <affiliation>beaton</affiliation>
          <note><p>Rev. John Beaton (c. 1640-1715) attended grammar school in Inveraray and
              graduated M.A. from Glasgow University in 1668. He was minister of Kilninian in Mull
              by 1679. In the 1680s, he was involved with the MacLeans of Duart in their ultimately
              doomed effort to resist Campbell domination of Mull. Then, in the 1690s, Rev. Beaton
              was removed from Kilninian, possibly due to his failure to adequately distance himself
              from Episcopalianism. He travelled to Ireland, where, in 1700, in Coleraine (Co.
              Derry), he famously met with and assisted Edward Lhuyd (1660-1709) in his
              investigations into Scottish Gaelic. At some point, he returned to Mull and died at
              Torrelock, not far from Kilninian, in 1714. Further reading: Bannerman, 1998:
              35-39</p></note>
        </handNote>
        <handNote xml:id="Hand17">
          <forename>Neil</forename>
          <surname>Beaton</surname>
          <date type="floruit" cert="medium" from="1656" to="1656" min="1600" max="1700">17</date>
          <region/>
          <affiliation>beaton</affiliation>
          <note>Black's Hand D (NLS Adv. 72.1.33)</note>
        </handNote>
        <handNote xml:id="Hand18">
          <forename>Dubhghall Albanach</forename>
          <surname>mac mhic Cathail</surname>
          <date type="floruit" cert="medium" from="1467" to="1467" min="1400" max="1500">15</date>
          <region>Ireland/Hebrides</region>
          <affiliation>DAC</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand19">
          <forename>Cairbre</forename>
          <surname>Ó Ceannmhain</surname>
          <date type="floruit" cert="high" from="1560" to="1563" min="1500" max="1600">16</date>
          <region/>
          <affiliation>misc</affiliation>
          <note>EUL La. III.21</note>
        </handNote>
        <handNote xml:id="Hand20">
          <forename>Eoin</forename>
          <surname>mac Domhnaill Ó Conchubhair</surname>
          <date type="life" cert="medium" from="1541" to="1601" min="1500" max="1600">16</date>
          <region>Lorne</region>
          <region>Ireland</region>
          <affiliation>ó_conchubair</affiliation>
          <note><p xml:space="preserve">
						- "a good, clear hand varying in size from average to large" (Black, 2011)
						- tendency to use accents frequently (related to stress patterns in Latinate vocab?); difficult to distinguish from hairstrokes on "i" in minim clusters.
						- subtle use of word division (e.g. copula plus prep. as well as copula plus pronoun).
						- flexible use of abbreviations (e.g. sed-abbreviation for "echt"; tironian et for "it").
					</p>
          </note>
        </handNote>
        <handNote xml:id="Hand21">
          <forename>Domhnall Gorm</forename>
          <surname>?Mac Domhnaill</surname>
          <date min="1501" max="1699">16/17</date>
          <region>Sleat (?)</region>
          <affiliation>misc</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand22">
          <forename>Cathal</forename>
          <surname>Mac Mhuirich</surname>
          <date min="1600" max="1700">17</date>
          <region>Western Isles/Skye</region>
          <affiliation>macmhuirich</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand23">
          <forename>Alasdair</forename>
          <surname>Mac Mhaighstir Alasdair</surname>
          <date min="1700" max="1800">18</date>
          <region>Western Highlands</region>
          <affiliation>AMD</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand24">
          <date type="floruit" min="1500" max="1600">16</date>
          <region cert="medium">Islay</region>
          <affiliation>beaton</affiliation>
          <note><p>This individual is unidentified but they appear to have been a physician active
              in Islay in the 16th century. In <ref type="mss" target="JRIr35">John Rylands MS. Ir
                35</ref> (fol. 67<hi rend="sup">r</hi>), he records a series of treatments performed
              there and makes reference to the same region in <ref target="NLS72127" type="mss">NLS
                Adv. 72.1.27</ref>, fol. 3<hi rend="sup">v</hi>. They also add frequent notes to
                <ref type="mss" target="MS14">MS14</ref>. In each of these cases and in that of <ref
                target="MS17" type="mss">MS17</ref>, Hand24 is interacting with a Beaton manuscript
              alongside Beaton scribes and glossators so he was evidently closely associated with
              this family, if not a member himself.</p></note>
        </handNote>
        <handNote xml:id="Hand25">
          <forename>Maol-Sheachlainn</forename>
          <surname>mac Iollainn Mhic an Leagha Ruaidh</surname>
          <date type="floruit" min="1450" max="1550" from="1512" to="1512">15/16</date>
          <region cert="high">Sligo</region>
          <region cert="high">Kildare</region>
          <affiliation>misc</affiliation>
          <note><p>This scribe appears to be based in Ireland. He contributes to manuscripts
              associated with Sligo and Kildare. In <ref target="MS17" type="mss">MS17</ref> (fol.
                100<hi rend="sup">r</hi>6), he calls Niall Óg mac Néill Mhic Beathadh "mo sesi",
              which could mean either "my friend" or, less likely, "my patron" and states that he
              wrote his potion of <ref target="MS17" type="mss">MS17</ref> for him. Bannerman (<ref
                target="Bannerman2015" type="bib">2015</ref>: 7–8) points out that the Beatons
              claims to be relatively close relations of Meic an Leagha Ruaidh.</p></note>
        </handNote>
        <handNote xml:id="Hand26">
          <forename>Maol Coluim</forename>
          <surname>mac Beathadh</surname>
          <date type="floruit" min="1501" max="1699" from="1582" to="1603">16/17</date>
          <region/>
          <affiliation>beaton</affiliation>
          <note><p>Hand G/H(?)/I(?) in NLS Adv. 72.1.33</p></note>
        </handNote>
        <handNote xml:id="Hand27">
          <date type="floruit" min="1501" max="1599">16</date>
          <region/>
          <affiliation>misc</affiliation>
          <note><p>Hand 5 in NLS Adv. 72.1.40; hand of Section B of NLS Adv. 72.1.31</p></note>
        </handNote>
        <handNote xml:id="Hand28">
          <forename>Fergus</forename>
          <surname>Beaton</surname>
          <date type="floruit" min="1350" max="1550" from="1408" to="1408">14/15</date>
          <region>Islay</region>
          <affiliation>beaton</affiliation>
        </handNote>
        <handNote xml:id="Hand29">
          <forename>Domhnall</forename>
          <surname>Mac Domhnaill</surname>
          <date type="floruit" min="1386" max="1423" from="1386" to="1423">14/15</date>
          <region>Islay</region>
          <affiliation>misc</affiliation>
          <note><p>Lord of the Isles (1386–1408)</p></note>
        </handNote>
        <handNote xml:id="Hand30">
          <date type="floruit" min="1601" max="1699">17</date>
          <region>Western Isles</region>
          <affiliation>macmhuirich</affiliation>
          <note><p>Intervenes briefly in NLS Adv. 72.1.48, at fol. 12v</p></note>
        </handNote>
        <handNote xml:id="Hand31">
          <forename>Toirdealbach</forename>
          <surname>Ó Muirgheasa</surname>
          <date type="floruit" from="1614" to="1614" min="1601" max="1699">17</date>
          <region>Skye</region>
          <affiliation>misc</affiliation>
          <note>Scribe (?) and first witness of the MacLeod Fosterage Contract (1614).</note>
        </handNote>
        <handNote xml:id="Hand32">
          <forename>Eoin</forename>
          <surname>Mac Colgan</surname>
          <date type="floruit" from="1614" to="1614" min="1601" max="1699">17</date>
          <region>Skye</region>
          <affiliation>misc</affiliation>
          <note>Minister of Bracadale; witness of the MacLeod Fosterage Contract (1614).</note>
        </handNote>
        <handNote xml:id="Hand33">
          <forename>Eoghan</forename>
          <surname>Mac Suibhne</surname>
          <date type="floruit" from="1614" to="1614" min="1601" max="1699">17</date>
          <region>Skye</region>
          <affiliation>misc</affiliation>
          <note>Minister of Duirinish; witness of the MacLeod Fosterage Contract (1614).</note>
        </handNote>
        <handNote xml:id="Hand34">
          <forename>Domhnall</forename>
          <surname>Mac Pail</surname>
          <date type="floruit" from="1614" to="1614" min="1601" max="1699">17</date>
          <region>Skye</region>
          <affiliation>misc</affiliation>
          <note>Witness of the MacLeod Fosterage Contract (1614).</note>
        </handNote>
        <handNote xml:id="Hand35">
          <date type="floruit" min="1501" max="1599">16</date>
          <note>Hand 48 in NLS Adv. 72.1.2.</note>
          <affiliation>beaton</affiliation>
        </handNote>
        <handNote xml:id="Hand36">
          <date type="floruit" min="1501" max="1599">16</date>
          <note>Hand 1 in NLS Adv. 72.1.27; "akin to those of Dáibhí Ó Cearnaigh, the Ó Ceannamháins
            and John Beaton in EUL MS. Laing III 21 and BL MS. Add. 15,582".</note>
          <affiliation>beaton</affiliation>
        </handNote>
        <handNote xml:id="Hand37">
          <date type="floruit" min="1501" max="1599">16</date>
          <note>Intervenes briefly in Hand 1's text in NLS Adv. 72.1.27.</note>
          <affiliation>beaton</affiliation>
        </handNote>
        <handNote xml:id="Hand38">
          <forename>Neil</forename>
          <surname>MacEwen</surname>
          <date type="floruit" from="1631" to="1631" min="1601" max="1699">17</date>
          <region>Argyll</region>
          <affiliation>mcewen</affiliation>
          <note>Scribe of 'Mór in broinsgel' in NAS RH. 13/40.</note>
        </handNote>
        <handNote xml:id="Hand39">
          <date type="floruit" min="1649" max="1705">17/18</date>
          <region>Skye</region>
          <affiliation>misc</affiliation>
          <note>Address Sir Norman McLeod in RIA E.i.3.</note>
        </handNote>
        <handNote xml:id="Hand40">
          <forename>Walter</forename>
          <surname>MacFarlane</surname>
          <date type="lifespan" min="1689" max="1767">18</date>
          <region>Argyll</region>
          <affiliation>misc</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand41">
          <forename>Uilleam</forename>
          <surname>Mac Mhurchaidh</surname>
          <date type="floruit" from="1750" to="1750" min="1701" max="1799">18</date>
          <region>Argyll</region>
          <affiliation>misc</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand42">
          <forename>Lachlan</forename>
          <surname>Mac Mhuirich</surname>
          <date type="floruit" from="1749" to="1749" min="1701" max="1799">18</date>
          <region>Argyll</region>
          <affiliation>macmhuirich</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand43">
          <forename>Domhnall</forename>
          <surname>Mac Mharcuis</surname>
          <date type="floruit" from="1700" to="1700" min="1701" max="1799">18</date>
          <region>Argyll</region>
          <affiliation>misc</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand44">
          <date type="floruit" min="1501" max="1599">16</date>
          <region>Argyll</region>
          <affiliation>beaton</affiliation>
          <note>Hand 2 in NLS Adv. 72.1.27</note>
        </handNote>
        <handNote xml:id="Hand45">
          <forename>Niall</forename>
          <date type="floruit" min="1501" max="1599">16</date>
          <region>Argyll</region>
          <affiliation>beaton</affiliation>
          <note>Hand 3 in NLS Adv. 72.1.27</note>
        </handNote>
        <handNote xml:id="Hand46">
          <forename>Christopher</forename>
          <surname>Beaton</surname>
          <date type="floruit" min="1601" max="1699">17</date>
          <region>Antrim</region>
          <affiliation>beaton</affiliation>
          <note>Scribe and redactor of the Black Book of Clanranald.</note>
        </handNote>
        <handNote xml:id="Hand47">
          <date type="floruit" min="1501" max="1599">16</date>
          <region>Argyll</region>
          <affiliation>beaton</affiliation>
          <note>Hand 4 in NLS Adv. 72.1.27</note>
        </handNote>
        <handNote xml:id="Hand48">
          <date type="floruit" min="1501" max="1599">16</date>
          <region>Argyll</region>
          <affiliation>beaton</affiliation>
          <note>Hand 6 in NLS Adv. 72.1.27</note>
        </handNote>
        <handNote xml:id="Hand49">
          <date type="floruit" min="1501" max="1599">16</date>
          <region>Argyll</region>
          <affiliation>beaton</affiliation>
          <note>Hand 7 in NLS Adv. 72.1.27</note>
        </handNote>
        <handNote xml:id="Hand50">
          <forename>[unk.]</forename>
          <surname>Beaton [?]</surname>
          <date type="floruit" min="1501" max="1599">16</date>
          <region>Argyll</region>
          <affiliation>beaton</affiliation>
          <note>Hand 8 in NLS Adv. 72.1.27</note>
        </handNote>
        <handNote xml:id="Hand52">
          <date type="floruit" min="1401" max="1499">15</date>
          <region>unknown</region>
          <affiliation>misc</affiliation>
          <note>Hand 2 in NLS Adv. 72.1.1</note>
        </handNote>
        <handNote xml:id="Hand53">
          <forename>Domhnall</forename>
          <surname>Ó Dálaigh</surname>
          <date type="floruit" min="1401" max="1499">15</date>
          <region>Western Highlands</region>
          <affiliation>misc</affiliation>
          <note/>
        </handNote>
        <handNote xml:id="Hand54">
          <date type="floruit" min="1601" max="1699">17</date>
          <region>Western Isles</region>
          <affiliation>macmhuirich</affiliation>
          <note>Main scribe of NLS Adv. 72.1.48</note>
        </handNote>
        <handNote xml:id="Hand55">
          <date type="floruit" min="1601" max="1699">17</date>
          <affiliation>misc</affiliation>
          <note>Scribe of 'Do bheirim barr búadha is áigh' in TCD 1362a.</note>
        </handNote>
        <handNote xml:id="Hand56">
          <date type="floruit" min="1501" max="1599">17</date>
          <forename>Níall Óg mac Néill</forename>
          <surname>Mhic Beathadh</surname>
          <region>Islay</region>
          <affiliation>beaton</affiliation>
          <note>Hand appears in NLS Adv. 72.1.4</note>
        </handNote>
        <handNote xml:id="Hand57">
          <forename>Níall</forename>
          <surname>Mac Beathadh</surname>
          <date type="floruit" min="1451" max="1549">15/16</date>
          <region>Islay</region>
          <affiliation>beaton</affiliation>
          <note>Hand appears in NLS Adv. 72.1.4</note>
        </handNote>
        <handNote xml:id="Hand58">
          <date type="floruit" min="1560" max="1560">16</date>
          <region>Argyll/Ulster</region>
          <affiliation>misc</affiliation>
          <note><p>The main scribe of the Argyll-O'Donnell Agreement, which he wrote out in 1560. He
              has not been identified and Black (2017) has stated that he could have been supplied
              by either Ó Domhnaill or Argyll. As Argyll is very much the dominant party in the
              arrangement, it seems marginally more likely that he supplied the scribe.</p>
            <p>The hand is generally careful and elegant, employing minimal abbreviation. It is
              somewhat eccentric orthographically, however (e.g. "tigerrna" for <hi rend="italics"
                >tigerna</hi>; "ghaodheal" for <hi rend="italics">Ghaoidheal</hi>).</p></note>
        </handNote>
        <handNote xml:id="Hand59">
          <date type="floruit" min="1601" max="1699">17</date>
          <region>Skye</region>
          <affiliation>misc</affiliation>
          <note>Hand H in RIA A.v.2.</note>
        </handNote>
        <handNote xml:id="Hand60">
          <date type="floruit" min="1560" max="1560">16</date>
          <region>Argyll/Ulster</region>
          <affiliation>misc</affiliation>
          <note>The hand that wrote out the witness list in the Argyll-O'Donnell Agreement
            (1560).</note>
        </handNote>
        <handNote xml:id="Hand61">
          <date type="floruit" min="1560" max="1560">16</date>
          <region>Argyll/Ulster</region>
          <affiliation>misc</affiliation>
          <note>The hand that wrote out Ó Domhnaill's signature in the Argyll-O'Donnell Agreement
            (1560); it is possible that this is An Calbhach Ó Domhnaill's (d. 1566) own hand.</note>
        </handNote>
        <handNote xml:id="Hand62">
          <date type="floruit" min="1601" max="1699">17</date>
          <region>Ireland</region>
          <affiliation>misc</affiliation>
          <note>The main hand of RIA A.iv.3 (743).</note>
        </handNote>
        <handNote xml:id="Hand63">
          <forename>Seanchán</forename>
          <surname>mac Maoil Mhuire Ó Mhaoil Chonaire</surname>
          <date type="floruit" min="1473" max="1473">15</date>
          <region>Ireland</region>
          <affiliation>misc</affiliation>
          <note>Seanchán mac Maoil Mhuire Ó Mhaoil Chonaire (fl. 1473) was a member of the prominent
            Ó Maoil Chonaire family of historians and poets, who were based mainly in Connacht.
            Seanchán mac Maoil Mhuire was responsible for transcribing and quite possibly compiling
            section 4 (cols 128–216) of YBL (TCD H.2.16 (1318)), a collection of bardic poetry. His
            hand is somewhat untidy by the standards of high-class medieval Gaelic manuscripts.
            Relative size of characters can vary markedly within the same word and strokes
            sporadically extend beyond the writing track. Taller letters can appear slanted and
            serifs are sometimes insufficiently pronounced to prevent minim confusion. That being
            said, ascenders and descenders are usually distinguished clearly, variation in stroke
            width contributes to letter forms, and serifs are usually in place and visible. </note>
        </handNote>
        <handNote xml:id="Hand64">
          <forename>Donnchadh Albannach</forename>
          <surname> Ó Conchubhair</surname>
          <date type="life" min="1571" max="1647">16/17</date>
          <region>Lorne</region>
          <affiliation>ó_conchubair</affiliation>
          <note>Completes a text begun by Neil Beaton (Hand17) in NLS Adv. MS 72.1.33 (MS5).</note>
        </handNote>
        <handNote xml:id="Hand65">
          <date type="floruit" min="1072" max="1082">11</date>
          <region>Mainz</region>
          <affiliation>misc</affiliation>
          <note>Amanuensis to Marianus Scotus (ob. 1082) in Codex Palatino-Vaticanus No. 830.</note>
        </handNote>
        <handNote xml:id="Hand66">
          <date type="floruit" min="1400" max="1499">15</date>
          <affiliation>misc</affiliation>
          <note>Hand 1 of NLS Adv. 72.1.3.</note>
        </handNote>
        <handNote xml:id="Hand67">
          <date type="floruit" min="1700" max="1799">18</date>
          <affiliation>misc</affiliation>
          <note>Writes out an elegy on Allan of Clanranald (ob. 1715).</note>
        </handNote>
        <handNote xml:id="Hand68">
          <date type="floruit" min="1600" max="1699">17</date>
          <affiliation>misc</affiliation>
          <note>Hand of The Seven Wise Masters (+ further texts?) in NLS Adv. 72.1.39.</note>
        </handNote>
        <handNote xml:id="Hand69">
          <date type="floruit" min="1400" max="1499">15</date>
          <forename>Gilla Padrig</forename>
          <surname>O Toindidh</surname>
          <affiliation>misc</affiliation>
          <note>Hand 2 of NLS Adv. 72.1.3.</note>
        </handNote>
        <handNote xml:id="Hand70">
          <date type="floruit" min="1600" max="1699">17</date>
          <forename>Domhnall na Foghlach</forename>
          <surname>Mac Bheathadh</surname>
          <affiliation>beaton</affiliation>
          <note>Signs EUL Laing III.21</note>
        </handNote>
        <handNote xml:id="Hand71">
          <date type="floruit" min="1500" max="1599">16</date>
          <forename>Domhnall</forename>
          <surname>mac Coinnigh</surname>
          <affiliation>ó_conchubair</affiliation>
          <note>Hand 3 of NLS Adv. 72.1.3.</note>
        </handNote>
        <handNote xml:id="Hand72">
          <date type="floruit" min="1500" max="1599">16</date>
          <forename>Gilla Coluim</forename>
          <affiliation>ó_conchubair</affiliation>
          <note>Adds a note to NLS Adv. 72.1.3 identifying Gilla Padrig Ó Toindidh as one of the
            main scribes.</note>
        </handNote>
        <handNote xml:id="Hand999">
          <forename>an unidentfied hand</forename>
          <date>unknown</date>
          <region>unknown</region>
          <affiliation>misc</affiliation>
          <note><p>This hand not only cannot be associated with a known scribe but cannot even be
              grouped in confidence with other interventions. This is probably because not enough
              has been written in this hand for a sound palaeographical analysis to be made. Note
              that this tag is a generic placeholder rather than an effort to identify a single
              hand.</p></note>
        </handNote>
        <handNote>
          <forename/>
          <surname/>
          <date/>
          <region/>
          <note/>
        </handNote>
      </handNotes>
XML;
	private $_id, $_element;

	/**
	 * hand constructor.
	 * @param $id
	 */
	public function __construct($id) {
		$this->_id = $id;
		$xml = new \SimpleXMLElement($this->_handNotes);
		$results = $xml->xpath("/handNotes/handNote[@xml:id='{$id}']");
		$this->_element = $results[0];
	}

	public function getId() {
		return $this->_id;
	}

	public function getSurname() {
		return $this->_element->surname;
	}

	public function getForename() {
		return $this->_element->forename;
	}

	public function getCentury() {
		return $this->_element->date;
	}

	public function getAffiliation() {
		return $this->_element->affiliation;
	}

	public function getRegion() {
		return $this->_element->region;
	}

	public function getNote() {
		return $this->_element->note;
	}

	public function getWriterId() {
		$db = new database();
		$result = $db->fetch("SELECT id FROM writer WHERE surname_en = :handId", array(":handId" => $this->getId()));
		return $result[0];
	}
}