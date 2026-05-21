<!DOCTYPE html>
<html lang="<?= h(getCurrentLanguage()) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(LOC('page.projects.title')) ?></title>
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <style>
        :root {
            --bg-a: #f6f1e8;
            --bg-b: #e9f0f8;
            --ink: #1d2b34;
            --panel: #ffffff;
            --line: #d1dbe4;
            --accent: #0f5a78;
            --accent-soft: #d9edf7;
            --warn: #af2f2f;
            --muted: #4a5f6d;
            --radius: 12px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI Variable", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: linear-gradient(160deg, var(--bg-a), var(--bg-b));
            min-height: 100vh;
        }

        .shell {
            width: min(1080px, 100% - 1.2rem);
            margin: 0 auto;
            padding: 0.8rem 0 1.4rem;
        }

        .topbar {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            position: sticky;
            top: 0.4rem;
            backdrop-filter: blur(3px);
            z-index: 30;
        }

        .logo {
            width: 38px;
            height: 38px;
            object-fit: contain;
            flex: 0 0 auto;
        }

        .titles h1 {
            margin: 0;
            font-size: 1.05rem;
        }

        .titles p {
            margin: 0.2rem 0 0;
            color: var(--muted);
            font-size: 0.84rem;
        }

        .lang {
            margin-left: auto;
            display: flex;
            gap: 0.35rem;
        }

        .lang a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 24px;
            border-radius: 6px;
            border: 1px solid var(--line);
            background: #fff;
        }

        .lang a svg {
            width: 24px;
            height: 16px;
            display: block;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 0.8rem;
            margin-top: 0.8rem;
        }

        .filters {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.55rem;
            align-items: end;
        }

        .filters label {
            display: block;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .filters select,
        .filters button {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 0.6rem 0.7rem;
            font-size: 0.92rem;
        }

        .filters button {
            background: var(--accent);
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }

        .progress-wrap {
            margin-top: 0.8rem;
            display: none;
            gap: 0.45rem;
            flex-direction: column;
        }

        .progress-wrap.is-visible {
            display: flex;
        }

        .progress-label {
            font-size: 0.88rem;
            color: var(--muted);
            font-weight: 700;
        }

        .progress-track {
            width: 100%;
            height: 12px;
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid var(--line);
            background: #eef3f7;
        }

        .progress-fill {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #1f7ea6, #2a9d6f);
            transition: width 180ms ease;
        }

        .alert {
            margin-top: 0.9rem;
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #f0b8b8;
            background: #fdeeee;
            color: var(--warn);
        }

        .company-block {
            margin-top: 1rem;
        }

        .company-title {
            margin: 0 0 0.6rem;
            font-size: 1rem;
            color: var(--accent);
        }

        .project-list {
            display: grid;
            gap: 0.65rem;
        }

        .project-card {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 12px;
            padding: 0.7rem;
            overflow: hidden;
            max-height: 1200px;
            opacity: 1;
            transition: max-height 280ms ease, opacity 220ms ease, padding 280ms ease, margin 280ms ease, border-width 200ms ease;
        }

        .project-card.is-entering {
            max-height: 0;
            opacity: 0;
            padding-top: 0;
            padding-bottom: 0;
            margin-top: 0;
            margin-bottom: 0;
            border-width: 0;
        }

        .project-head {
            margin: 0;
            font-size: 0.95rem;
        }

        .project-description {
            margin: 0.35rem 0 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .workorder-list {
            list-style: none;
            margin: 0.65rem 0 0;
            padding: 0;
            border-top: 1px dashed var(--line);
        }

        .workorder-list li {
            padding: 0.55rem 0;
            border-bottom: 1px dashed #e6edf3;
            font-size: 0.9rem;
        }

        .workorder-list li:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .workorder-row {
            overflow: hidden;
            max-height: 200px;
            opacity: 1;
            transition: max-height 260ms ease, opacity 200ms ease, padding 260ms ease, margin 260ms ease;
        }

        .workorder-row.is-entering {
            max-height: 0;
            opacity: 0;
            padding-top: 0;
            padding-bottom: 0;
            margin-top: 0;
            margin-bottom: 0;
            border-bottom: 0;
        }

        .empty {
            margin: 0.6rem 0 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        @media (min-width: 760px) {
            .filters {
                grid-template-columns: 1fr auto;
                gap: 0.8rem;
            }

            .filters button {
                width: auto;
                min-width: 140px;
            }

            .project-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        <header class="topbar">
            <img class="logo" src="logo-website.png" alt="Kubera">
            <div class="titles">
                <h1><?= h(LOC('page.projects.heading')) ?></h1>
                <p><?= h(LOC('page.projects.intro')) ?></p>
            </div>
            <nav class="lang" aria-label="Language switch">
                <?php foreach (['nl', 'en', 'de', 'fr'] as $language): ?>
                    <a href="?<?= h(http_build_query(array_merge($_GET, ['lang' => $language]))) ?>"
                        title="<?= h(strtoupper($language)) ?>">
                        <?= getLanguageFlagSvg($language) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </header>

        <section class="panel">
            <form method="get" class="filters">
                <div>
                    <label for="company"><?= h(LOC('filter.company')) ?></label>
                    <select id="company" name="company">
                        <option value="__all__" <?= $selectedCompany === '' ? 'selected' : '' ?>>
                            <?= h(LOC('filter.all_companies')) ?>
                        </option>
                        <?php foreach ($availableCompanies as $companyName): ?>
                            <option value="<?= h($companyName) ?>" <?= $selectedCompany === $companyName ? 'selected' : '' ?>>
                                <?= h($companyName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit"><?= h(LOC('filter.apply')) ?></button>
            </form>
        </section>

        <section class="panel progress-wrap" id="progress-wrap">
            <div class="progress-label" id="progress-label"><?= h(LOC('progress.waiting')) ?></div>
            <div class="progress-track" aria-hidden="true">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
        </section>

        <?php if ($odataError !== null && $odataError !== ''): ?>
            <div class="alert" id="odata-alert">
                <?= h(LOC('error.odata_failed')) ?>
                <?php if (!empty($showOdataErrorDetails)): ?>
                    <br>
                    <small><?= h($odataError) ?></small>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div id="odata-alert"></div>
        <?php endif; ?>

        <div id="results-root"></div>

        <?= injectTimerHtml([
            'statusUrl' => 'odata.php?action=cache_status',
            'deleteUrl' => 'odata.php?action=cache_delete',
            'clearUrl' => 'odata.php?action=cache_clear',
            'title' => 'Cachebestanden',
            'label' => 'Cache',
            'css' => '{{root}} .odata-cache-widget{position:fixed;top:14px;right:14px;}{{root}} .odata-cache-popout{position:fixed;top:56px;right:14px;}'
        ]) ?>
    </div>

    <script>
        (function ()
        {
            const labels = {
                waiting: <?= json_encode(LOC('progress.waiting'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                done: <?= json_encode(LOC('progress.done'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                failed: <?= json_encode(LOC('progress.failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                stepWorkorders: <?= json_encode(LOC('progress.step_workorders'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                stepProjects: <?= json_encode(LOC('progress.step_projects'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                filterDepartment: <?= json_encode(LOC('filter.cost_center_code'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                filterDepartmentAll: <?= json_encode(LOC('filter.cost_center_code_all'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                noProjects: <?= json_encode(LOC('msg.no_projects_found'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                noWorkorders: <?= json_encode(LOC('msg.no_workorders_for_project'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                tableCompany: <?= json_encode(LOC('table.company'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                tableJob: <?= json_encode(LOC('table.job'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                tableDepartment: <?= json_encode(LOC('table.cost_center_code'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                tableWorkOrder: <?= json_encode(LOC('table.work_order'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                tableDescription: <?= json_encode(LOC('table.description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                sectionReadyToInvoiceIn: <?= json_encode(LOC('section.ready_to_invoice_in', '%s'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                errorMessage: <?= json_encode(LOC('error.odata_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
            };

            const formEl = document.querySelector('.filters');
            const companyEl = document.getElementById('company');
            let departmentEl = document.getElementById('department');
            const progressWrapEl = document.getElementById('progress-wrap');
            const progressLabelEl = document.getElementById('progress-label');
            const progressFillEl = document.getElementById('progress-fill');
            const resultsRootEl = document.getElementById('results-root');
            const alertEl = document.getElementById('odata-alert');
            const renderedState = {};
            const initialSelectedDepartment = <?= json_encode((string) ($selectedDepartment ?? '__all__'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            let initialDepartmentApplied = false;
            const domState = {
                companies: {},
                projects: {}
            };

            function escapeHtml (value)
            {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function setProgress (stepIndex, stepTotal, stepKey, completedBatches, totalBatches, percent)
            {
                const safePercent = Math.max(0, Math.min(100, Number(percent || 0)));
                progressWrapEl.classList.add('is-visible');
                progressFillEl.style.width = safePercent + '%';

                let stepLabel = labels.stepWorkorders;

                progressLabelEl.textContent = 'Stap ' + stepIndex + '/' + stepTotal + ': ' + safePercent + '% - '
                    + stepLabel + ' (' + completedBatches + '/' + totalBatches + ' batches)';
            }

            function mergeBatch (company, projects)
            {
                if (!company || !Array.isArray(projects))
                {
                    return;
                }

                if (!renderedState[company])
                {
                    renderedState[company] = {};
                }

                for (const project of projects)
                {
                    const projectNo = String((project && project.no) || '').trim();
                    if (!projectNo)
                    {
                        continue;
                    }

                    if (!renderedState[company][projectNo])
                    {
                        renderedState[company][projectNo] = {
                            no: projectNo,
                            description: String((project && project.description) || ''),
                            department_code: String((project && project.department_code) || ''),
                            workorders: []
                        };
                    }

                    const existingProject = renderedState[company][projectNo];
                    if (existingProject.department_code === '' && project && project.department_code)
                    {
                        existingProject.department_code = String(project.department_code);
                    }
                    const workorders = Array.isArray(project.workorders) ? project.workorders : [];

                    for (const workorder of workorders)
                    {
                        const workorderNo = String((workorder && workorder.no) || '').trim();
                        if (!workorderNo)
                        {
                            continue;
                        }

                        const duplicate = existingProject.workorders.some(function (entry)
                        {
                            return String(entry.no || '') === workorderNo;
                        });

                        if (!duplicate)
                        {
                            existingProject.workorders.push({
                                no: workorderNo,
                                description: String((workorder && workorder.description) || '')
                            });
                        }
                    }

                    existingProject.workorders.sort(function (a, b)
                    {
                        return String(a.no || '').localeCompare(String(b.no || ''), undefined, { numeric: true, sensitivity: 'base' });
                    });
                }
            }

            function snapshotState ()
            {
                const output = {};
                const companies = Object.keys(renderedState).sort(function (a, b)
                {
                    return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
                });

                for (const company of companies)
                {
                    const projectsMap = renderedState[company] || {};
                    const projects = Object.keys(projectsMap).map(function (projectNo)
                    {
                        return projectsMap[projectNo];
                    });

                    projects.sort(function (a, b)
                    {
                        return String(a.no || '').localeCompare(String(b.no || ''), undefined, { numeric: true, sensitivity: 'base' });
                    });

                    output[company] = projects;
                }

                return output;
            }

            function createCompanySection (companyName)
            {
                const section = document.createElement('section');
                section.className = 'panel company-block';

                const title = document.createElement('h2');
                title.className = 'company-title';
                title.textContent = labels.sectionReadyToInvoiceIn.replace('%s', companyName);

                const empty = document.createElement('p');
                empty.className = 'empty';
                empty.textContent = labels.noProjects;

                const projectList = document.createElement('div');
                projectList.className = 'project-list';

                section.appendChild(title);
                section.appendChild(empty);
                section.appendChild(projectList);
                resultsRootEl.appendChild(section);

                domState.companies[companyName] = {
                    section: section,
                    emptyEl: empty,
                    listEl: projectList
                };

                return domState.companies[companyName];
            }

            function getCompanySection (companyName)
            {
                return domState.companies[companyName] || createCompanySection(companyName);
            }

            function animateIn (element)
            {
                element.classList.add('is-entering');
                requestAnimationFrame(function ()
                {
                    requestAnimationFrame(function ()
                    {
                        element.classList.remove('is-entering');
                    });
                });
            }

            function createProjectCard (companyName, project)
            {
                const companySection = getCompanySection(companyName);
                const card = document.createElement('article');
                card.className = 'project-card';

                const title = document.createElement('h3');
                title.className = 'project-head';
                title.textContent = labels.tableJob + ': ' + String(project.no || '');

                const department = document.createElement('p');
                department.className = 'project-description';
                department.textContent = labels.tableDepartment + ': ' + String(project.department_code || '');

                const empty = document.createElement('p');
                empty.className = 'empty';
                empty.textContent = labels.noWorkorders;

                const list = document.createElement('ul');
                list.className = 'workorder-list';
                list.style.display = 'none';

                card.appendChild(title);
                card.appendChild(department);
                card.appendChild(empty);
                card.appendChild(list);

                companySection.listEl.appendChild(card);
                animateIn(card);

                const key = companyName + '||' + String(project.no || '');
                domState.projects[key] = {
                    cardEl: card,
                    departmentEl: department,
                    emptyEl: empty,
                    listEl: list,
                    workorderIds: {},
                    departmentCode: String(project.department_code || '')
                };

                return domState.projects[key];
            }

            function getProjectCard (companyName, project)
            {
                const key = companyName + '||' + String(project.no || '');
                return domState.projects[key] || createProjectCard(companyName, project);
            }

            function applyDepartmentFilterToDom ()
            {
                const selectedDepartment = departmentEl ? String(departmentEl.value || '__all__') : '__all__';
                const companyKeys = Object.keys(domState.companies);
                let totalVisibleCount = 0;

                const existingGlobalEmpty = resultsRootEl.querySelector('[data-global-empty="1"]');
                if (existingGlobalEmpty)
                {
                    existingGlobalEmpty.remove();
                }

                for (const companyName of companyKeys)
                {
                    const sectionState = domState.companies[companyName];
                    let visibleCount = 0;

                    for (const projectKey of Object.keys(domState.projects))
                    {
                        if (!projectKey.startsWith(companyName + '||'))
                        {
                            continue;
                        }

                        const projectState = domState.projects[projectKey];
                        const isVisible = selectedDepartment === '__all__'
                            || String(projectState.departmentCode || '') === selectedDepartment;

                        projectState.cardEl.style.display = isVisible ? '' : 'none';
                        if (isVisible)
                        {
                            visibleCount++;
                        }
                    }

                    totalVisibleCount += visibleCount;

                    sectionState.emptyEl.style.display = visibleCount === 0 ? '' : 'none';
                    sectionState.section.style.display = visibleCount === 0 && selectedDepartment !== '__all__' ? 'none' : '';
                }

                const hasVisibleResults = totalVisibleCount > 0;
                if (!hasVisibleResults)
                {
                    const emptySection = document.createElement('section');
                    emptySection.className = 'panel';
                    emptySection.setAttribute('data-global-empty', '1');
                    emptySection.innerHTML = '<p class="empty">' + escapeHtml(labels.noProjects) + '</p>';
                    resultsRootEl.insertBefore(emptySection, resultsRootEl.firstChild);
                }
            }

            function appendWorkorderToProjectCard (projectState, workorder)
            {
                const workorderNo = String((workorder && workorder.no) || '').trim();
                if (workorderNo === '' || projectState.workorderIds[workorderNo])
                {
                    return;
                }

                const row = document.createElement('li');
                row.className = 'workorder-row';
                row.innerHTML = '<strong>' + escapeHtml(labels.tableWorkOrder) + ':</strong> ' + escapeHtml(workorderNo)
                    + '<br><strong>' + escapeHtml(labels.tableDescription) + ':</strong> '
                    + escapeHtml(String((workorder && workorder.description) || ''));

                projectState.listEl.appendChild(row);
                animateIn(row);

                projectState.workorderIds[workorderNo] = true;
                projectState.emptyEl.style.display = 'none';
                projectState.listEl.style.display = '';
            }

            function applyBatchToDom (company, projects)
            {
                if (!company || !Array.isArray(projects))
                {
                    return;
                }

                for (const project of projects)
                {
                    const projectNo = String((project && project.no) || '').trim();
                    if (projectNo === '')
                    {
                        continue;
                    }

                    const projectState = getProjectCard(company, project);
                    const newDepartment = String((project && project.department_code) || '').trim();
                    if (projectState.departmentCode === '' && newDepartment !== '')
                    {
                        projectState.departmentCode = newDepartment;
                        projectState.departmentEl.textContent = labels.tableDepartment + ': ' + newDepartment;
                    }

                    const workorders = Array.isArray(project.workorders) ? project.workorders : [];
                    for (const workorder of workorders)
                    {
                        appendWorkorderToProjectCard(projectState, workorder);
                    }
                }

                applyDepartmentFilterToDom();
            }

            async function getDepartmentCodesFromSource ()
            {
                try
                {
                    const response = await fetch('cache/departments.json', {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!response.ok)
                    {
                        return [];
                    }

                    const payload = await response.json();
                    const codes = Array.isArray(payload && payload.codes)
                        ? payload.codes
                        : (Array.isArray(payload) ? payload : []);

                    return codes
                        .map(function (value) { return String(value || '').trim(); })
                        .filter(function (value) { return value !== ''; })
                        .sort(function (a, b)
                        {
                            return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
                        });
                } catch (error)
                {
                    return [];
                }
            }

            function ensureDepartmentFilter ()
            {
                if (departmentEl)
                {
                    return;
                }

                const wrapper = document.createElement('div');
                const label = document.createElement('label');
                label.setAttribute('for', 'department');
                label.textContent = labels.filterDepartment;

                departmentEl = document.createElement('select');
                departmentEl.id = 'department';
                departmentEl.name = 'department';

                wrapper.appendChild(label);
                wrapper.appendChild(departmentEl);

                if (formEl && formEl.firstElementChild)
                {
                    formEl.insertBefore(wrapper, formEl.firstElementChild.nextSibling);
                } else if (formEl)
                {
                    formEl.insertBefore(wrapper, formEl.firstChild);
                }

                departmentEl.addEventListener('change', function ()
                {
                    saveUserPreferences();
                    applyDepartmentFilterToDom();
                });
            }

            async function refreshDepartmentOptions ()
            {
                ensureDepartmentFilter();
                if (!departmentEl)
                {
                    return;
                }

                const previousValue = String(departmentEl.value || '__all__');
                const codes = await getDepartmentCodesFromSource();

                let optionsHtml = '<option value="__all__">' + escapeHtml(labels.filterDepartmentAll) + '</option>';
                for (const code of codes)
                {
                    optionsHtml += '<option value="' + escapeHtml(code) + '">' + escapeHtml(code) + '</option>';
                }

                departmentEl.innerHTML = optionsHtml;
                if (!initialDepartmentApplied && initialSelectedDepartment !== '__all__' && codes.includes(initialSelectedDepartment))
                {
                    departmentEl.value = initialSelectedDepartment;
                    initialDepartmentApplied = true;
                } else if (previousValue !== '__all__' && codes.includes(previousValue))
                {
                    departmentEl.value = previousValue;
                }

                applyDepartmentFilterToDom();
            }

            async function saveUserPreferences ()
            {
                const companyValue = companyEl ? String(companyEl.value || '__all__') : '__all__';
                const departmentValue = departmentEl ? String(departmentEl.value || '__all__') : '__all__';

                try
                {
                    await fetch('project_workorders_userprefs.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            company: companyValue === '__all__' ? '' : companyValue,
                            department: departmentValue
                        })
                    });
                } catch (error)
                {
                    // Ignore preference save failures; do not block loading.
                }
            }

            function setAlert (message)
            {
                if (!alertEl)
                {
                    return;
                }

                if (!message)
                {
                    alertEl.innerHTML = '';
                    return;
                }

                alertEl.innerHTML = '<div class="alert">' + escapeHtml(message) + '</div>';
            }

            function resetRenderedState ()
            {
                for (const company of Object.keys(renderedState))
                {
                    delete renderedState[company];
                }

                for (const projectKey of Object.keys(domState.projects))
                {
                    delete domState.projects[projectKey];
                }

                for (const companyName of Object.keys(domState.companies))
                {
                    delete domState.companies[companyName];
                }

                resultsRootEl.innerHTML = '';
            }

            function getCompaniesToLoad ()
            {
                if (!companyEl)
                {
                    return [];
                }

                const selected = String(companyEl.value || '__all__');
                if (selected !== '__all__')
                {
                    return [selected];
                }

                return Array.from(companyEl.options)
                    .map(function (option) { return String(option.value || ''); })
                    .filter(function (value) { return value !== '' && value !== '__all__'; });
            }

            async function fetchBatch (company, skip)
            {
                const params = new URLSearchParams();
                params.set('company', company);
                params.set('skip', String(skip));
                params.set('top', '10');

                const response = await fetch('project_workorders_batch.php?' + params.toString(), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.ok !== true)
                {
                    throw new Error((payload && payload.error) ? String(payload.error) : labels.errorMessage);
                }

                return payload;
            }

            async function startLoad ()
            {
                setAlert('');
                resultsRootEl.innerHTML = '';
                resetRenderedState();
                await refreshDepartmentOptions();

                progressWrapEl.classList.add('is-visible');
                progressFillEl.style.width = '0%';
                progressLabelEl.textContent = labels.waiting;

                const companiesToLoad = getCompaniesToLoad();
                await saveUserPreferences();
                if (companiesToLoad.length === 0)
                {
                    progressLabelEl.textContent = labels.done;
                    applyDepartmentFilterToDom();
                    return;
                }

                let completedBatches = 0;
                let totalBatches = 0;

                try
                {
                    for (const company of companiesToLoad)
                    {
                        let skip = 0;
                        let isDone = false;
                        let firstBatch = true;

                        while (!isDone)
                        {
                            const payload = await fetchBatch(company, skip);
                            if (firstBatch)
                            {
                                const count = Number(payload.total_count || 0);
                                const size = Math.max(1, Number(payload.page_size || 10));
                                const planned = Math.max(1, Math.ceil(count / size));
                                totalBatches += planned;
                                firstBatch = false;
                            }

                            mergeBatch(String(payload.company || company), Array.isArray(payload.projects) ? payload.projects : []);
                            await refreshDepartmentOptions();
                            applyBatchToDom(String(payload.company || company), Array.isArray(payload.projects) ? payload.projects : []);

                            completedBatches++;
                            const percent = totalBatches > 0 ? Math.floor((completedBatches / totalBatches) * 100) : 0;
                            setProgress(1, 1, 'workorders', completedBatches, Math.max(1, totalBatches), percent);

                            isDone = Boolean(payload.done) || Number(payload.fetched || 0) === 0;
                            skip = Number(payload.next_skip || skip);
                        }
                    }

                    progressLabelEl.textContent = labels.done;
                    progressFillEl.style.width = '100%';
                } catch (error)
                {
                    progressLabelEl.textContent = labels.failed;
                    setAlert(error instanceof Error ? error.message : labels.errorMessage);
                }
            }

            if (formEl)
            {
                formEl.addEventListener('submit', function (event)
                {
                    event.preventDefault();
                    startLoad();
                });
            }

            startLoad();
        })();
    </script>
</body>

</html>