wmcontent
======================

[![Latest Stable Version](https://poser.pugx.org/wieni/wmcontent/v/stable)](https://packagist.org/packages/wieni/wmcontent)
[![Total Downloads](https://poser.pugx.org/wieni/wmcontent/downloads)](https://packagist.org/packages/wieni/wmcontent)
[![License](https://poser.pugx.org/wieni/wmcontent/license)](https://packagist.org/packages/wieni/wmcontent)

> Adds configurable entity containers to entity types (for e.g. paragraphs)

## Installation

This package requires PHP 7.1 and Drupal 8.5 or higher. It can be
installed using Composer:

```bash
 composer require wieni/wmcontent
```

## How does it work?
### Terminology
- A **container** connects hosts of a certain type to children of a
  certain type.
- A **host** is an entity with containers, e.g. a node with a paragraph
  container. A host can have multiple children per container.
- A **child** is an entity attached to a host through a container, e.g.
  a paragraph. Every entity can only be attached to a single container.

Children and hosts can be entities of any type with a canonical route,
implementing `Drupal\Core\Entity\ContentEntityTypeInterface`. 

When updating a child entity, the changed time of the host entity is
updated as well.


### Get started
Before you begin, make sure your user role has the `administer
wmcontent` permission. After that, you can get started by creating a new
container. You can do this by going to
`/admin/config/wmcontent/containers` or by following the _Structure_ >
_WmContent_ > _WmContent Containers_ menu link.

After creating the container, go to the (edit) page of a possible host
entity. A new tab should have appeared with the name of the container
you just created.

Clicking that link brings you to the master form, where you can add
children to and edit/delete/reorder children from this host. 

### Displaying children
If you use Display Suite to build your pages, you can use the
_WmContent: Content blocks_ field to display the content of the children
entities on a host.

You can change the way wmcontent containers are rendered by overriding
the `wmcontent` theme implementation.

If you build your pages manually using Twig templates, you can load the
children of a host using `WmContentManagerInterface::getContent`.

## Changelog
All notable changes to this project will be documented in the
[CHANGELOG](CHANGELOG.md) file.

## Security
If you discover any security-related issues, please email
[security@wieni.be](mailto:security@wieni.be) instead of using the issue
tracker.

## License
Distributed under the MIT License. See the [LICENSE](LICENSE.md) file
for more information.
