<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>
  <xsl:output omit-xml-declaration = "yes" />

  <xsl:include href="common-templates.xslt" />

  <xsl:param name="zetaprints-api-url" />

  <xsl:template match="TemplateDetails">
    <xsl:apply-templates select="Pages" />
  </xsl:template>

  <xsl:template match="Pages">
    <xsl:for-each select="Page">
      <xsl:if test="//Fields/Field[@Page=position()]">
        <div id="input-fields-page-{position()}" class="zetaprints-page-input-fields">
          <xsl:call-template name="fields-for-page">
            <xsl:with-param name="page" select="position()" />
          </xsl:call-template>
          <xsl:text>&#x0A;</xsl:text>
        </div>
      </xsl:if>
    </xsl:for-each>
  </xsl:template>
</xsl:stylesheet>
