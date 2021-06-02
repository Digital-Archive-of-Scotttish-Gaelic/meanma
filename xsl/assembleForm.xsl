<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  exclude-result-prefixes="xs"
  version="1.0">
  
  <xsl:strip-space elements="*"/>
  <xsl:output method="html"/>
  
  <xsl:template match="name|w">
    <xsl:apply-templates/>
  </xsl:template>
  
  <xsl:template match="g[starts-with(@ref, 'g')]">
    <em class="{@id}">
      <xsl:apply-templates/>
    </em>
  </xsl:template>

  <xsl:template match="add[@type = 'insertion']">
    <sup>
      <xsl:apply-templates/>
    </sup>
  </xsl:template>

  <xsl:template match="del">
    <span style="text-decoration: line-through; text-decoration-style: double;">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="unclear[@reason='damage' or @reason='writing_surface_lost']">
    <small class="text-muted">[..]</small>
  </xsl:template>
  
  <xsl:template match="supplied">
    <xsl:text>[</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>]</xsl:text>
  </xsl:template>
  
  <xsl:template match="space">
    <xsl:text> </xsl:text>
  </xsl:template>
  
  <xsl:template match="pc"/> <!-- maybe restrict to within bottom-level words? -->
    
  <xsl:template match="note"/>  

</xsl:stylesheet>
