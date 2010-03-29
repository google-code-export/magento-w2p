<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>
  <xsl:output omit-xml-declaration = "yes" />

  <xsl:include href="common-templates.xslt" />

  <xsl:param name="zetaprints-api-url" />

  <xsl:template match="TemplateDetails">
    <xsl:apply-templates select="Pages" />
  </xsl:template>

  <xsl:template match="Pages">
    <xsl:for-each select="Page">
      <a id="preview-image-page-{position()}" class="zetaprints-template-preview" href="{$zetaprints-api-url}{@PreviewImage}">
        <img src="{$zetaprints-api-url}{@PreviewImage}">
          <xsl:attribute name="title">
            <xsl:call-template name="trans">
              <xsl:with-param name="key">Click to enlarge image</xsl:with-param>
            </xsl:call-template>
          </xsl:attribute>
        </img>
      </a>
    </xsl:for-each>
  </xsl:template>
</xsl:stylesheet>
