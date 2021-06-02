<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:tei="http://www.tei-c.org/ns/1.0"
  exclude-result-prefixes="xs tei" version="1.0">

  <!-- <xsl:strip-space elements="*"/> -->
  <xsl:output method="html"/>

  <xsl:param name="num"/>

  <xsl:template match="/">
    <xsl:apply-templates select="tei:TEI/tei:text/tei:body/tei:div"/>
  </xsl:template>

  <xsl:template match="tei:div">
    <div>
      <small class="text-muted numbers">[start of <span title="{concat(@type,' ',@corresp)}">Text
            <xsl:value-of select="@n"/></span>]</small>
    </div>
    <xsl:apply-templates/>
    <div>
      <small class="text-muted numbers">[end of Text <xsl:value-of select="@n"/>]</small>
    </div>
  </xsl:template>

  <xsl:template match="tei:p">
    <p>
      <xsl:apply-templates/>
    </p>
  </xsl:template>

  <xsl:template match="tei:handShift">
    <small class="text-muted numbers">[hs]</small>
  </xsl:template>

  <xsl:template match="tei:lb">
    <small class="text-muted numbers">
      <xsl:text>(</xsl:text>
      <xsl:value-of select="@n"/>
      <xsl:text>)</xsl:text>
    </small>
  </xsl:template>

  <xsl:template match="tei:pb">
    <small class="numbers">
      <mark>
        <xsl:text>[p.</xsl:text>
        <xsl:choose>
          <xsl:when test="@facs">
            <a href="#" class="page" data-facs="{@facs}">
              <xsl:value-of select="@n"/>
            </a>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="@n"/>
          </xsl:otherwise>
        </xsl:choose>
        <xsl:text>]</xsl:text>
      </mark>
    </small>
  </xsl:template>

  <xsl:template match="tei:cb">
    <small class="numbers">
      <mark>
        <xsl:text>[col.</xsl:text>
        <xsl:value-of select="@n"/>
        <xsl:text>]</xsl:text>
      </mark>
    </small>
  </xsl:template>

  <xsl:template match="tei:note">
    <xsl:if test="$num = 'yes'">
      <xsl:text>*</xsl:text>
      <!-- <a href="#" title="{normalize-space(.)}">[*]</a> -->
    </xsl:if>
  </xsl:template>

  <xsl:template
    match="tei:name[not(ancestor::tei:name) and not(ancestor::tei:w)] | tei:w[not(ancestor::tei:name) and not(ancestor::tei:w)]">
    <!-- Dubhghaill, Drostan, Calum cille -->
    <xsl:text> </xsl:text>
    <span class="chunk" id="{@id}">
      <xsl:apply-templates/>
    </span>
    <xsl:text> </xsl:text>
  </xsl:template>

  <xsl:template
    match="tei:name[ancestor::tei:name or ancestor::tei:w] | tei:w[ancestor::tei:name or ancestor::tei:w]">
    <xsl:text> </xsl:text>
    <xsl:apply-templates/>
    <xsl:text> </xsl:text>
  </xsl:template>



  <!--
  <xsl:template match="tei:w[not(ancestor::tei:w) and not(ancestor::tei:name) and not(descendant::tei:w)]">
    <xsl:text> </xsl:text>
    <span class="word chunk syntagm">
      <xsl:call-template name="addWordAttributes"/>
      <xsl:apply-templates/>
    </span>
    <xsl:text> </xsl:text>
  </xsl:template>

  <xsl:template match="tei:w[not(ancestor::tei:w) and not(ancestor::tei:name) and descendant::tei:w]">
    <xsl:text> </xsl:text>
    <span class="chunk syntagm">
      <xsl:call-template name="addWordAttributes"/>
      <xsl:apply-templates/>
    </span>
    <xsl:text> </xsl:text>
  </xsl:template>

  <xsl:template match="tei:w[@pos = 'verb' and not(@lemmaRef = 'http://www.dil.ie/29104') and (ancestor::tei:w or ancestor::tei:name) and not(descendant::tei:w)]">
    <xsl:text> </xsl:text>
    <span class="word syntagm">
      <xsl:call-template name="addWordAttributes"/>
      <xsl:apply-templates/>
    </span>
    <xsl:text> </xsl:text>
  </xsl:template>

  <xsl:template match="tei:w[(not(@pos = 'verb') or @lemmaRef = 'http://www.dil.ie/29104') and ancestor::tei:name and not(ancestor::tei:w) and not(descendant::tei:w)]">
    <xsl:text> </xsl:text>
    <span class="word syntagm apple">
      <xsl:call-template name="addWordAttributes"/>
      <xsl:apply-templates/>
    </span>
    <xsl:text> </xsl:text>
  </xsl:template>

  <xsl:template match="tei:w[(not(@pos = 'verb') or @lemmaRef = 'http://www.dil.ie/29104') and ancestor::tei:w and not(descendant::tei:w)]">
    <span class="word syntagm banana">
      <xsl:call-template name="addWordAttributes"/>
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:w">
    <xsl:text> </xsl:text>
    <span class="syntagm">
      <xsl:call-template name="addWordAttributes"/>
      <xsl:apply-templates/>
    </span>
    <xsl:text> </xsl:text>
  </xsl:template>
  
  -->




  <xsl:template match="tei:pc[ancestor::tei:w and not(ancestor::tei:supplied)]"/> 

  <xsl:template match="tei:pc[ancestor::tei:w and ancestor::tei:supplied]">
    <xsl:value-of select="."/>
  </xsl:template>

  <xsl:template match="tei:pc[not(ancestor::tei:w)]">
    <span class="chunk">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:c | tei:date | tei:num | tei:seg[@type = 'fragment']">
    <span class="chunk">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:g[starts-with(@ref, 'g')]">
    <em id="{@id}">
      <xsl:apply-templates/>
    </em>
  </xsl:template>

  <xsl:template match="tei:abbr">
    <xsl:apply-templates/>
  </xsl:template>

  <xsl:template match="tei:g[starts-with(@ref, 'l')]">
    <span class="ligature" data-glyphref="{@ref}" id="{generate-id(.)}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:space[@type = 'force']">
    <xsl:text> </xsl:text>
  </xsl:template>

  <xsl:template match="tei:del">
    <span style="text-decoration: line-through; text-decoration-style: double;">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:add[@type = 'gloss']">
    <span class="gloss" data-place="{@place}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:add[@type = 'insertion']">
    <sup>
      <xsl:apply-templates/>
    </sup>
  </xsl:template>

  <xsl:template match="tei:choice">
    <xsl:text>{</xsl:text>
    <xsl:apply-templates select="tei:corr"/>
    <xsl:text>}</xsl:text>
  </xsl:template>







  <xsl:template match="tei:lg">
    <table>
      <tr>
        <td style="vertical-align: top; width: 50px;" class="stanzaNumber">
          <small class="text-muted">
            <xsl:value-of select="@n"/>. </small>
        </td>
        <td>
          <xsl:apply-templates/>
        </td>
      </tr>
    </table>
    <p> </p>
  </xsl:template>

  <xsl:template match="tei:l">
    <xsl:apply-templates/>
    <br/>
  </xsl:template>


  <!--
    <xsl:template match="tei:supplied[tei:w]">   editorial insertions containing words e.g. T1.5r.25 [a]
      <span class="supplied" data-resp="{@resp}">
        <xsl:apply-templates/>
      </span>
    </xsl:template>
  -->

  <xsl:template match="tei:supplied">
    <xsl:text>[</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>]</xsl:text>
  </xsl:template>

  <xsl:template match="tei:unclear[@reason = 'damage' or @reason = 'writing_surface_lost']">
    <span style="background-color: lightgray;">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:unclear[@reason = 'text_obscure']">
    <span style="background-color: lightgray;">
      <xsl:apply-templates/>
    </span>
  </xsl:template>




  <xsl:template match="tei:unclear[@reason = 'interp_obscure']">
    <!-- e.g. MS6.2r.1 [t] -->
    <span class="unclearInterpObscure" data-cert="{@cert}" data-resp="{@resp}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:unclear[@reason = 'char']">
    <!-- MS6.2r.7 [i] -->
    <span class="unclearCharSemi" data-cert="{@cert}" data-resp="{@resp}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>



  <xsl:template match="tei:gap">
    <small class="text-muted chunk"> [...] </small>
  </xsl:template>

  <xsl:template match="tei:gap[@unit = 'folio']">
    <span class="gapDamageDiplo" data-quantity="{@quantity}" data-unit="{@unit}" data-resp="{@resp}">
      <xsl:text> [</xsl:text>
      <xsl:value-of select="@quantity"/>
      <xsl:text> missing folio(s)] </xsl:text>
    </span>
  </xsl:template>

  <xsl:template match="tei:anchor">
    <xsl:variable name="target" select="@copyOf"/>
    <span class="pageBreak">
      <br/>
      <small class="text-muted">
        <xsl:text>[</xsl:text>
        <xsl:value-of select="@comment"/>
        <xsl:text>]</xsl:text>
      </small>
    </span>
    <xsl:apply-templates
      select="document(concat('../../Transcribing/Transcriptions/transcription', @source, '.xml'))/descendant::tei:div[@corresp = $target]"
    />
  </xsl:template>

  <xsl:template match="tei:seg[@type = 'cfe']">
    <!-- no idea what this means -->
    <span class="syntagm">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:seg[@type = 'catchword']"/>


</xsl:stylesheet>
