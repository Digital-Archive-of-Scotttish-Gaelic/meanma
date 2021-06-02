<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:tei="http://www.tei-c.org/ns/1.0" exclude-result-prefixes="xs tei" version="1.0">

  <xsl:strip-space elements="tei:*"/>
  <xsl:output method="html"/>

  <xsl:param name="num"/>

  <xsl:template match="/">
    <xsl:apply-templates select="tei:TEI/tei:text/tei:body/tei:div"/>
  </xsl:template>

  <xsl:template match="tei:div">
    <xsl:apply-templates/>
  </xsl:template>

  <xsl:template match="tei:pb">
    <br/>
    <small class="text-muted numbers">[start of page <xsl:value-of select="@n"/>]</small>
  </xsl:template>

  <xsl:template match="tei:handShift">
    <small class="text-muted numbers">[hs]</small>
  </xsl:template>

  <xsl:template match="tei:lb">
    <br/>
    <small class="text-muted numbers">
      <xsl:value-of select="@n"/>
      <xsl:text>. </xsl:text>
    </small>
  </xsl:template>

  <xsl:template match="tei:cb">
    <br/>
    <small class="text-muted numbers">[start of column <xsl:value-of select="@n"/>]</small>
  </xsl:template>

  <xsl:template match="tei:lg | tei:l | tei:p | tei:abbr">
    <!-- ignore these elements in diplo view -->
    <xsl:apply-templates/>
  </xsl:template>

  <xsl:template match="tei:supplied | tei:note | tei:head/tei:title"/>
  <!-- *completely* ignore these elements in diplo view -->

  <xsl:template match="tei:space">
    <xsl:text> </xsl:text>
  </xsl:template>

  <xsl:template match="tei:pc[ancestor::tei:w]"> <!-- maybe restrict to within bottom-level words? -->
    <xsl:value-of select="."/>
  </xsl:template>

  <xsl:template match="tei:pc[not(ancestor::tei:w)]">
      <xsl:apply-templates/>
  </xsl:template>

  <xsl:template match="tei:c | tei:date | tei:num | tei:seg[@type = 'fragment'] | tei:seg[@type = 'cfe']">
    <span class="weird">
      <!-- clickable? -->
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:name[not(ancestor::tei:name) and not(ancestor::tei:w)] | tei:w[not(ancestor::tei:name) and not(ancestor::tei:w)]">
      <xsl:apply-templates/>
  </xsl:template>

  <xsl:template match="tei:name[ancestor::tei:name or ancestor::tei:w] | tei:w[ancestor::tei:name or ancestor::tei:w]">
    <xsl:apply-templates/>
  </xsl:template>

  <xsl:template match="tei:g[starts-with(@ref, 'g')]">
    <em id="{@id}">
      <xsl:apply-templates/>
    </em>
  </xsl:template>

  <xsl:template match="tei:add[@type = 'insertion']">
    <sup>
      <xsl:apply-templates/>
    </sup>
  </xsl:template>

  <xsl:template match="tei:del">
    <span style="text-decoration: line-through; text-decoration-style: double;">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:unclear[(@reason='damage' or @reason='writing_surface_lost') and ancestor::tei:w]">
    <small class="text-muted">[..]</small>
  </xsl:template>

  <xsl:template match="tei:unclear[(@reason='damage' or @reason='writing_surface_lost') and not(ancestor::tei:w)]">
    <span class="chunk">
      <small class="text-muted">[..]</small>
    </span>
  </xsl:template>

  <xsl:template match="tei:choice">
    <xsl:text>{</xsl:text>
    <xsl:apply-templates select="tei:sic"/>
    <xsl:text>}</xsl:text>
  </xsl:template>

  <xsl:template match="tei:unclear[@reason='text_obscure']">
    <!-- e.g. MS6.2r.1 [t] -->
    <span style="background-color: lightgray;">
      <xsl:apply-templates/>
    </span>
  </xsl:template>






  <xsl:template match="tei:unclear[@reason = 'char']">
    <!-- MS6.2r.7 [i] -->
    <span class="unclearCharDiplo" data-cert="{@cert}" data-resp="{@resp}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:unclear[@reason = 'interp_obscure']">
    <!-- ??? -->
    <span class="unclearInterpObscure" data-cert="{@cert}" data-resp="{@resp}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="tei:add[@type = 'gloss']">
    <span class="gloss" data-place="{@place}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>



  <!-- Marginal notes - Added by SB-->
  <xsl:template match="tei:seg[@type = 'margNote']">
    <!-- e.g. ? -->
    <xsl:text> </xsl:text>
    <a href="#" class="marginalNoteLink" data-id="{@xml:id}">m</a>
    <div class="marginalNote" id="{@xml:id}">
      <xsl:apply-templates/>
    </div>
  </xsl:template>

  <xsl:template match="tei:gap[@unit = 'folio']">
    <!-- needs work -->
    <br/>
    <span class="missingFolio" data-quantity="{@quantity}" data-unit="{@unit}" data-resp="{@resp}">
      <small class="text-muted">
        <xsl:text> [</xsl:text>
        <xsl:value-of select="@quantity"/>
        <xsl:text> missing folio(s)] </xsl:text>
      </small>
    </span>
  </xsl:template>

  <xsl:template match="tei:gap">
    <small class="text-muted chunk">[...]</small>
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
    <xsl:apply-templates select="document(concat('../../Transcribing/Transcriptions/transcription', @source, '.xml'))/descendant::tei:div[@corresp = $target]"/>
  </xsl:template>

  <xsl:template match="tei:seg[@type = 'catchword']">
    <xsl:apply-templates/>
  </xsl:template>

</xsl:stylesheet>
