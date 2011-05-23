<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>

  <xsl:output omit-xml-declaration = "yes" />

  <xsl:include href="common-templates.xslt" />

  <xsl:param name="page-size-units" />
  <xsl:param name="page-size-icon" />

  <xsl:template match="TemplateDetails">
    <div class="zetaprints-page-size-table">
      <xsl:call-template name="page-size" />
    </div>
  </xsl:template>

</xsl:stylesheet>
