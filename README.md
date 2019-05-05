![Smart Cohort](docs/logo.png?raw=true)

# Smart Cohort #

We developed the Smart Cohort module based on our years of experience in Moodle operation and development.

Course creators, site administrators and teachers are surely familiar with cohorts in the system that allow new colleagues / students to be easily enrolled in the appropriate courses. The users only have to be added to the group and the rest is automatically arranged by the system.

The Smart Cohort Module allows authorized users to define filtering criteria and to specify which cohort the filtered users would be added to. This add-on works well with any authentication plugins, so when the user is created or its data gets updated, the system checks for which groups to add to or remove the user from based on the filtering criteria.

The module was originally developed for our partners, but we feel like that this community would also find it useful, so we've made it available to you for free. With the help of this module it's easier for our partners to identify courses based on area, job, position, and they do not have to deal with managing cohorts.

## Version support ##

Minimum: Moodle 3.5.4

Maximum: 3.6.3+

## Screenshots ##

Empty cohorts

![](docs/001_available_cohorts.png?raw=true)

Create filter

![](docs/007_create_with_2_rules.png?raw=true)
![](docs/008_rules.png?raw=true)

After that, you can see the filter is in progress

![](docs/009_initializing.png?raw=true)

Filter is initialized by scheduled task

![](docs/010_initialized.png?raw=true)

After that, check your cohorts and see the magic

![](docs/011_cohort_uploaded.png?raw=true)


## Installation ##

1. Install the plugin the same as any standard moodle plugin either via the
Moodle plugin directory, or you can use git to clone it into your source:

     `git clone git@github.com:cnw-co/moodle-local_cnw_smartcohort.git local/cnw_smartcohort`
     
      Or install via the Moodle plugin directory:
         
      https://moodle.org/plugins/local_cnw_smartcohort
     
2. Then run the Moodle upgrade

    If you have issues please log them in github here:
    
    https://github.com/cnw-co/moodle-local_cnw_smartcohort/issues
    
3. Go to `Dashboard ► Site administration ► Users ► Accounts ► Smart Cohort` and create your first filter


## License ##

CNW Rendszerintegrációs Zrt. <moodle@cnw.hu>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
