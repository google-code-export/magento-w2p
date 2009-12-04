<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>
  <xsl:output omit-xml-declaration = "yes" />

  <xsl:param name="zetaprints-api-url" />

  <xsl:template match="TemplateDetails">
    <xsl:apply-templates select="Pages" />
  </xsl:template>

  <xsl:template match="Pages">

    <div class="zetaprints-template-preview-images">

      <xsl:for-each select="Page">
        <div id="preview-image-page-{position()}" class="zetaprints-template-preview">
          <a href="{$zetaprints-api-url}{@PreviewImage}">
            <img title="Click to view in large size" src="{$zetaprints-api-url}{@PreviewImage}" />
          </a>
        </div>
      </xsl:for-each>

    </div>

  </xsl:template>
</xsl:stylesheet>
