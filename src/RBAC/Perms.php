<?php
namespace DriveJob\RBAC;

final class Perms {
    // Jobs
    public const JOBS_VIEW      = "jobs.view";
    public const JOBS_CREATE    = "jobs.create";
    public const JOBS_EDIT_OWN  = "jobs.edit.own";
    public const JOBS_EDIT_ANY  = "jobs.edit.any";
    public const JOBS_DELETE_OWN= "jobs.delete.own";
    public const JOBS_DELETE_ANY= "jobs.delete.any";

    // Applications
    public const APPL_VIEW_OWN  = "applications.view.own";
    public const APPL_VIEW_ANY  = "applications.view.any";
    public const APPL_MANAGE_OWN= "applications.manage.own";
    public const APPL_MANAGE_ANY= "applications.manage.any"; // newly added

    // Matches
    public const MATCHES_VIEW_OWN = "matches.view.own";
    public const MATCHES_VIEW_ANY = "matches.view.any"; // newly added

    // Drivers
    public const DRIVERS_VIEW_ANY   = "drivers.view.any";
    public const DRIVERS_EDIT_SELF  = "drivers.edit.self";
    public const DRIVERS_VIEW_CAND  = "drivers.view.candidates";

    // Ratings / Favorites
    public const RATINGS_CREATE   = "ratings.create";
    public const RATINGS_VIEW_OWN = "ratings.view.own";
    public const FAVORITES_USE    = "favorites.use";

    // Admin
    public const ADMIN_ACCESS   = "admin.access";
    public const RBAC_MANAGE    = "rbac.manage";
}
