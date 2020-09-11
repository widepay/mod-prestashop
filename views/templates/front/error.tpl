{extends file=$layout}

{block name='content'}

<section id="main">

{block name='page_header_container'}
{block name='page_title' hide}
<header class="page-header">
<h1>{$smarty.block.child}</h1>
</header>
{/block}
{/block}

{block name='page_content_container'}
<section id="content" class="page-content card card-block">
{block name='page_content_top'}{/block}
{block name='page_content'}

<div class="box">

<div class="alert alert-danger">
Ocorreu um erro no processamento da cobrança Wide Pay.<br>
{$erro nofilter}
</div>

</div>
{/block}
</section>
{/block}

</section>
{/block}