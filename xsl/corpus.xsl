<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:dasg="https://dasg.ac.uk/corpus/"
  exclude-result-prefixes="xs"
  version="1.0">
  
  <xsl:strip-space elements="*"/>
  <xsl:output encoding="UTF-8" method="html"/>
  
  <xsl:template match="/">
    <div>
      <xsl:apply-templates/>
    </div>
  </xsl:template>

  <xsl:template match="dasg:text">
    <xsl:apply-templates/>
  </xsl:template>
  
  <xsl:template match="dasg:include">
    <xsl:variable name="file" select="@href"/>
    <xsl:variable name="ref" select="document($file)/dasg:text/@ref"/>
    <p>
      <a href="viewText.php?ref={$ref}">
        <xsl:value-of select="$ref"/>
      </a>
    </p>
  </xsl:template>
  
  <xsl:template match="dasg:h">
    <p>
      <strong>
        <xsl:apply-templates/>
      </strong>
    </p>
  </xsl:template>
  
  <xsl:template match="dasg:p">
    <p>
      <xsl:apply-templates/>  
    </p>
  </xsl:template>
  
  <xsl:template match="dasg:u">
    <p>
      <small class="text-muted">[<xsl:value-of select="@ref"/>]</small>
      <br/>
      <xsl:apply-templates/>  
    </p>
  </xsl:template>
  
  <xsl:template match="dasg:lg">
    <p>
      <xsl:apply-templates/>  
    </p>
  </xsl:template>
  
  <xsl:template match="dasg:l">
    <xsl:apply-templates/>
    <br/>
  </xsl:template>
  
  <xsl:template match="dasg:o[name(following-sibling::*[1])='w' or name(following-sibling::*[1])='s']">
    <span class="text-muted">
      <xsl:apply-templates/>
    </span>
    <xsl:text> </xsl:text>
  </xsl:template>
  
  <xsl:template match="dasg:o">
    <span class="text-muted">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <xsl:template match="dasg:w[name(following-sibling::*[1])='w' or name(following-sibling::*[1])='o' or name(following-sibling::*[1])='i' or name(following-sibling::*[1])='footnote']">
    <span class="word" data-toggle="tooltip" data-placement="top">
      <xsl:attribute name="id">
        <xsl:value-of select="@id"/>
      </xsl:attribute>
      <xsl:attribute name="title">
        <xsl:if test="@lemma">
          <xsl:text>lemma: </xsl:text>
          <xsl:value-of select="@lemma"/>
        </xsl:if>
        <xsl:if test="@pos">
          <xsl:text> pos: </xsl:text>
          <xsl:value-of select="@pos"/>
        </xsl:if>
      </xsl:attribute>
      <xsl:attribute name="data-ref">
        <xsl:value-of select="@ref"/>
      </xsl:attribute>
      <xsl:apply-templates/>
    </span>
    <xsl:text> </xsl:text>
  </xsl:template>
  
  <xsl:template match="dasg:w">
    <span class="word" data-toggle="tooltip" data-placement="top">
      <xsl:attribute name="id">
        <xsl:value-of select="@id"/>
      </xsl:attribute>
      <xsl:attribute name="title">
        <xsl:if test="@lemma">
          <xsl:text>lemma: </xsl:text>
          <xsl:value-of select="@lemma"/>
        </xsl:if>
        <xsl:if test="@pos">
          <xsl:text> pos: </xsl:text>
          <xsl:value-of select="@pos"/>
        </xsl:if>
      </xsl:attribute>
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  
  <xsl:template match="dasg:pc[@join='right']">
    <xsl:text> </xsl:text>
    <xsl:apply-templates/>
  </xsl:template>
  
  <xsl:template match="dasg:pc[@join='left']">
    <xsl:apply-templates/>
    <xsl:text> </xsl:text>
  </xsl:template>
  
  <xsl:template match="dasg:pc[@join='no']">
    <xsl:text> </xsl:text>
    <xsl:apply-templates/>
    <xsl:text> </xsl:text>
  </xsl:template>
  
  <xsl:template match="dasg:pc">
    <xsl:apply-templates/>
  </xsl:template>
  
  <xsl:template match="dasg:t">
    <small>
      <a href="#">
        <xsl:attribute name="title">
          <xsl:value-of select="."/>
        </xsl:attribute>
        <xsl:text>[en]</xsl:text>
      </a>
    </small>
  </xsl:template>
  
  <xsl:template match="dasg:pause">
    <p> </p>
  </xsl:template>
  
  <xsl:template match="dasg:i[name(following-sibling::*[1])='pc' and following-sibling::*[1]/@join='left']">
    <i>
      <xsl:apply-templates/>
    </i>
  </xsl:template>
  
  <xsl:template match="dasg:i">
    <i>
      <xsl:apply-templates/>
    </i>
    <xsl:text> </xsl:text>
  </xsl:template>
  
  <xsl:template match="dasg:pb">
    <xsl:if test="not(name(following-sibling::*[1])='pb')">
      <hr/>
    </xsl:if>
    <xsl:choose>
      <xsl:when test="@img and starts-with(@img,'http')">
        <a class="link externalLink" data-url="{@img}">[<xsl:value-of select="@n"/>]</a>
        <xsl:text>&#32;</xsl:text>
      </xsl:when>
      <xsl:when test="@img">
        <a class="link scanLink" data-pdf="{@img}">[<xsl:value-of select="@n"/>]</a>
        <xsl:text>&#32;</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <span class="text-muted">[<xsl:value-of select="@n"/>] </span>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="dasg:footnote">
    <sup class="text-muted">
      <xsl:text>[</xsl:text>
      <xsl:apply-templates select="dasg:p/*"/>
      <xsl:text>] </xsl:text>
    </sup>
  </xsl:template>
  
  <xsl:template match="dasg:list">
    <table class="table">
      <tbody>
        <xsl:apply-templates select="dasg:label"/>
      </tbody>
    </table>
  </xsl:template>
  
  <xsl:template match="dasg:label">
    <tr>
      <th>
        <xsl:apply-templates/>
      </th>
      <td>
        <xsl:apply-templates select="following-sibling::dasg:item[1]"/>
      </td>
    </tr>
  </xsl:template>
  
  <xsl:template match="dasg:note">
    <p class="text-muted small">
      <xsl:apply-templates/>
    </p>
  </xsl:template>
  
  <xsl:template match="dasg:n">
    <xsl:if test="not(name(preceding::*[1])='pc' and preceding::*[1]/@join='right')">
      <xsl:text> </xsl:text>
    </xsl:if>
    <u style="text-decoration-color: red; text-decoration-style: dashed">
      <xsl:apply-templates/>
    </u>
    <xsl:if test="not(name(following::*[1])='pc' and following::*[1]/@join='left')">
      <xsl:text> </xsl:text>
    </xsl:if>
  </xsl:template>
  
  <xsl:template match="dasg:w/dasg:lb">
    <small class="text-muted"><xsl:text>(</xsl:text><xsl:value-of select="@n"/><xsl:text>)</xsl:text></small>
  </xsl:template>
  
  <xsl:template match="dasg:lb">
    <small class="text-muted"><xsl:text> (</xsl:text><xsl:value-of select="@n"/><xsl:text>) </xsl:text></small>
  </xsl:template>
  
  <xsl:template match="dasg:s">
    <xsl:text> </xsl:text>
    <xsl:apply-templates/>
    <xsl:text> </xsl:text>
  </xsl:template>
  
</xsl:stylesheet>